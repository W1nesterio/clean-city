package com.example.cleancity;

import android.Manifest;
import android.content.pm.PackageManager;
import android.content.SharedPreferences;
import android.content.res.ColorStateList;
import android.graphics.Color;
import android.net.Uri;
import android.os.Bundle;
import android.view.Gravity;
import android.view.View;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;
import androidx.appcompat.app.AlertDialog;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.content.ContextCompat;
import androidx.core.content.FileProvider;

import com.example.cleancity.api.ApiClient;
import com.example.cleancity.api.ApiService;
import com.example.cleancity.models.CreateTicketResponse;
import com.example.cleancity.models.Ticket;
import com.example.cleancity.models.TicketStatusRequest;
import com.example.cleancity.models.TicketsResponse;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.card.MaterialCardView;
import com.google.android.material.dialog.MaterialAlertDialogBuilder;

import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.util.List;

import okhttp3.MediaType;
import okhttp3.MultipartBody;
import okhttp3.RequestBody;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class TicketListActivity extends AppCompatActivity {

    private TextView titleTextView;
    private LinearLayout ticketsContainer;
    private ProgressBar progressBar;
    private ApiService apiService;
    private String mode;
    private String token;
    private int ticketIdForAfterPhoto = -1;
    private Uri pendingAfterCameraPhotoUri;

    private final ActivityResultLauncher<String> afterGalleryPhotoLauncher = registerForActivityResult(
            new ActivityResultContracts.GetContent(),
            uri -> {
                if (uri == null) {
                    Toast.makeText(this, "Фото не выбрано", Toast.LENGTH_SHORT).show();
                    ticketIdForAfterPhoto = -1;
                    return;
                }
                Toast.makeText(this, "Фото выбрано из галереи", Toast.LENGTH_SHORT).show();
                completeTicketWithPhoto(uri);
            }
    );

    private final ActivityResultLauncher<Uri> afterCameraPhotoLauncher = registerForActivityResult(
            new ActivityResultContracts.TakePicture(),
            success -> {
                if (Boolean.TRUE.equals(success) && pendingAfterCameraPhotoUri != null) {
                    Toast.makeText(this, "Фото сделано с камеры", Toast.LENGTH_SHORT).show();
                    completeTicketWithPhoto(pendingAfterCameraPhotoUri);
                } else {
                    ticketIdForAfterPhoto = -1;
                    Toast.makeText(this, "Съёмка отменена", Toast.LENGTH_SHORT).show();
                }
            }
    );

    private final ActivityResultLauncher<String> cameraPermissionLauncher = registerForActivityResult(
            new ActivityResultContracts.RequestPermission(),
            granted -> {
                if (Boolean.TRUE.equals(granted)) {
                    openAfterCamera();
                } else {
                    ticketIdForAfterPhoto = -1;
                    Toast.makeText(this, "Разрешение на камеру не выдано", Toast.LENGTH_LONG).show();
                }
            }
    );

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_ticket_list);

        titleTextView = findViewById(R.id.listTitleTextView);
        ticketsContainer = findViewById(R.id.ticketsContainer);
        progressBar = findViewById(R.id.listProgressBar);

        SharedPreferences preferences = getSharedPreferences("auth", MODE_PRIVATE);
        String serverUrl = preferences.getString("server_url", ApiClient.DEFAULT_BASE_URL);
        apiService = ApiClient.getClient(serverUrl).create(ApiService.class);
        mode = getIntent().getStringExtra("mode");
        if (mode == null) {
            mode = "my";
        }

        token = preferences.getString("token", "");

        if ("worker".equals(mode)) {
            titleTextView.setText("Назначенные заявки");
        } else {
            titleTextView.setText("Мои заявки");
        }

        loadTickets();
    }

    private void loadTickets() {
        if (token == null || token.trim().isEmpty()) {
            Toast.makeText(this, "Авторизация не найдена. Войдите заново", Toast.LENGTH_LONG).show();
            return;
        }

        progressBar.setVisibility(View.VISIBLE);
        ticketsContainer.removeAllViews();

        Call<TicketsResponse> call = "worker".equals(mode)
                ? apiService.getWorkerTickets("Bearer " + token)
                : apiService.getMyTickets("Bearer " + token);

        call.enqueue(new Callback<TicketsResponse>() {
            @Override
            public void onResponse(Call<TicketsResponse> call, Response<TicketsResponse> response) {
                progressBar.setVisibility(View.GONE);

                if (response.isSuccessful() && response.body() != null) {
                    showTickets(response.body().getTickets());
                } else {
                    Toast.makeText(TicketListActivity.this, "Не удалось загрузить заявки", Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onFailure(Call<TicketsResponse> call, Throwable t) {
                progressBar.setVisibility(View.GONE);
                Toast.makeText(TicketListActivity.this, "Ошибка подключения: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    private void showTickets(List<Ticket> tickets) {
        ticketsContainer.removeAllViews();

        if (tickets == null || tickets.isEmpty()) {
            TextView emptyText = new TextView(this);
            emptyText.setText("Заявок пока нет");
            emptyText.setTextSize(18);
            emptyText.setTextColor(Color.parseColor("#6B7280"));
            emptyText.setPadding(10, 30, 10, 10);
            ticketsContainer.addView(emptyText);
            return;
        }

        for (Ticket ticket : tickets) {
            ticketsContainer.addView(createTicketCard(ticket));
        }
    }

    private View createTicketCard(Ticket ticket) {
        MaterialCardView card = new MaterialCardView(this);
        LinearLayout.LayoutParams cardParams = new LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
        );
        cardParams.setMargins(0, 0, 0, dp(14));
        card.setLayoutParams(cardParams);
        card.setRadius(dp(22));
        card.setCardElevation(dp(6));
        card.setCardBackgroundColor(Color.WHITE);
        card.setContentPadding(dp(18), dp(18), dp(18), dp(18));

        LinearLayout content = new LinearLayout(this);
        content.setOrientation(LinearLayout.VERTICAL);

        LinearLayout headerRow = new LinearLayout(this);
        headerRow.setOrientation(LinearLayout.HORIZONTAL);
        headerRow.setGravity(Gravity.CENTER_VERTICAL);

        TextView title = new TextView(this);
        String categoryName = ticket.getCategory() != null ? ticket.getCategory().getName() : "Категория";
        title.setText("Заявка №" + ticket.getId() + " · " + categoryName);
        title.setTextColor(Color.parseColor("#1F2933"));
        title.setTextSize(18);
        title.setTypeface(null, android.graphics.Typeface.BOLD);
        LinearLayout.LayoutParams titleParams = new LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1);
        title.setLayoutParams(titleParams);
        headerRow.addView(title);

        if ("my".equals(mode)) {
            MaterialButton deleteMenuButton = new MaterialButton(this);
            deleteMenuButton.setText("⋮");
            deleteMenuButton.setTextSize(22);
            deleteMenuButton.setTextColor(Color.parseColor("#4B5563"));
            deleteMenuButton.setAllCaps(false);
            deleteMenuButton.setCornerRadius(dp(16));
            deleteMenuButton.setBackgroundTintList(ColorStateList.valueOf(Color.parseColor("#F3F4F6")));
            deleteMenuButton.setMinWidth(dp(44));
            deleteMenuButton.setMinimumWidth(dp(44));
            deleteMenuButton.setPadding(0, 0, 0, 0);
            LinearLayout.LayoutParams menuParams = new LinearLayout.LayoutParams(dp(44), dp(44));
            menuParams.setMargins(dp(10), 0, 0, 0);
            deleteMenuButton.setLayoutParams(menuParams);
            deleteMenuButton.setOnClickListener(v -> showDeleteConfirmation(ticket));
            headerRow.addView(deleteMenuButton);
        }

        content.addView(headerRow);

        TextView status = new TextView(this);
        status.setText("Статус: " + ticket.getStatusLabel());
        status.setTextColor(Color.parseColor(statusColor(ticket.getStatus())));
        status.setTextSize(14);
        status.setTypeface(null, android.graphics.Typeface.BOLD);
        status.setPadding(0, dp(8), 0, 0);
        content.addView(status);

        TextView description = new TextView(this);
        String desc = ticket.getDescription() != null ? ticket.getDescription() : "Без описания";
        description.setText(desc);
        description.setTextColor(Color.parseColor("#6B7280"));
        description.setTextSize(14);
        description.setPadding(0, dp(8), 0, 0);
        content.addView(description);

        TextView place = new TextView(this);
        place.setText("Место указано");
        place.setTextColor(Color.parseColor("#6B7280"));
        place.setTextSize(13);
        place.setPadding(0, dp(6), 0, 0);
        content.addView(place);

        if ("worker".equals(mode)) {
            if ("completed".equals(ticket.getStatus())) {
                TextView completedText = new TextView(this);
                completedText.setText("Заявка выполнена");
                completedText.setTextColor(Color.parseColor("#166534"));
                completedText.setTextSize(14);
                completedText.setTypeface(null, android.graphics.Typeface.BOLD);
                completedText.setPadding(0, dp(12), 0, 0);
                content.addView(completedText);
            } else {
                LinearLayout buttons = new LinearLayout(this);
                buttons.setOrientation(LinearLayout.VERTICAL);
                buttons.setPadding(0, dp(14), 0, 0);

                LinearLayout firstRow = new LinearLayout(this);
                firstRow.setOrientation(LinearLayout.HORIZONTAL);

                MaterialButton acceptButton = makeSmallButton("Принять", "#146C43");
                acceptButton.setOnClickListener(v -> changeStatus(ticket.getId(), "accepted", "Заявка принята исполнителем"));
                firstRow.addView(acceptButton);

                MaterialButton progressButton = makeSmallButton("В работу", "#1D4ED8");
                LinearLayout.LayoutParams progressParams = new LinearLayout.LayoutParams(0, dp(52), 1);
                progressParams.setMargins(dp(8), 0, 0, 0);
                progressButton.setLayoutParams(progressParams);
                progressButton.setOnClickListener(v -> changeStatus(ticket.getId(), "in_progress", "Исполнитель начал выполнение"));
                firstRow.addView(progressButton);

                buttons.addView(firstRow);

                MaterialButton completeButton = makeFullButton("Завершить с фото", "#166534");
                completeButton.setOnClickListener(v -> openAfterPhotoPicker(ticket.getId()));
                buttons.addView(completeButton);

                content.addView(buttons);
            }
        }

        card.addView(content);
        return card;
    }

    private MaterialButton makeSmallButton(String text, String color) {
        MaterialButton button = new MaterialButton(this);
        button.setText(text);
        button.setTextSize(14);
        button.setSingleLine(false);
        button.setMaxLines(2);
        button.setAllCaps(false);
        button.setCornerRadius(dp(14));
        button.setBackgroundTintList(android.content.res.ColorStateList.valueOf(Color.parseColor(color)));
        LinearLayout.LayoutParams params = new LinearLayout.LayoutParams(0, dp(52), 1);
        button.setLayoutParams(params);
        return button;
    }

    private MaterialButton makeFullButton(String text, String color) {
        MaterialButton button = new MaterialButton(this);
        button.setText(text);
        button.setTextSize(14);
        button.setSingleLine(false);
        button.setMaxLines(2);
        button.setAllCaps(false);
        button.setCornerRadius(dp(14));
        button.setBackgroundTintList(android.content.res.ColorStateList.valueOf(Color.parseColor(color)));
        LinearLayout.LayoutParams params = new LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, dp(52));
        params.setMargins(0, dp(10), 0, 0);
        button.setLayoutParams(params);
        return button;
    }

    private String statusColor(String status) {
        if (status == null) return "#146C43";
        switch (status) {
            case "created": return "#0369A1";
            case "assigned": return "#92400E";
            case "accepted": return "#5B21B6";
            case "in_progress": return "#1D4ED8";
            case "completed": return "#166534";
            case "rejected": return "#991B1B";
            default: return "#146C43";
        }
    }

    private void changeStatus(int ticketId, String status, String comment) {
        if (token == null || token.trim().isEmpty()) {
            Toast.makeText(this, "Авторизация не найдена. Войдите заново", Toast.LENGTH_LONG).show();
            return;
        }

        progressBar.setVisibility(View.VISIBLE);

        apiService.changeStatus(
                "Bearer " + token,
                ticketId,
                new TicketStatusRequest(status, comment)
        ).enqueue(new Callback<CreateTicketResponse>() {
            @Override
            public void onResponse(Call<CreateTicketResponse> call, Response<CreateTicketResponse> response) {
                progressBar.setVisibility(View.GONE);
                if (response.isSuccessful()) {
                    Toast.makeText(TicketListActivity.this, "Статус изменён", Toast.LENGTH_SHORT).show();
                    loadTickets();
                } else {
                    Toast.makeText(TicketListActivity.this, "Не удалось изменить статус", Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onFailure(Call<CreateTicketResponse> call, Throwable t) {
                progressBar.setVisibility(View.GONE);
                Toast.makeText(TicketListActivity.this, "Ошибка подключения: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    private void openAfterPhotoPicker(int ticketId) {
        ticketIdForAfterPhoto = ticketId;
        new MaterialAlertDialogBuilder(this)
                .setTitle("Фото результата")
                .setItems(new CharSequence[]{"Камера", "Галерея"}, (dialog, which) -> {
                    if (which == 0) {
                        openAfterCameraWithPermission();
                    } else {
                        afterGalleryPhotoLauncher.launch("image/*");
                    }
                })
                .setNegativeButton("Отмена", (dialog, which) -> ticketIdForAfterPhoto = -1)
                .show();
    }

    private void openAfterCameraWithPermission() {
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.CAMERA) == PackageManager.PERMISSION_GRANTED) {
            openAfterCamera();
        } else {
            cameraPermissionLauncher.launch(Manifest.permission.CAMERA);
        }
    }

    private void openAfterCamera() {
        try {
            pendingAfterCameraPhotoUri = createCameraPhotoUri();
            afterCameraPhotoLauncher.launch(pendingAfterCameraPhotoUri);
        } catch (IOException e) {
            ticketIdForAfterPhoto = -1;
            Toast.makeText(this, "Не удалось открыть камеру", Toast.LENGTH_LONG).show();
        }
    }

    private Uri createCameraPhotoUri() throws IOException {
        File dir = new File(getCacheDir(), "images");
        if (!dir.exists() && !dir.mkdirs()) {
            throw new IOException("Не удалось создать папку для фото");
        }

        File file = File.createTempFile("after_photo_", ".jpg", dir);
        return FileProvider.getUriForFile(this, getPackageName() + ".fileprovider", file);
    }

    private void completeTicketWithPhoto(Uri photoUri) {
        if (ticketIdForAfterPhoto <= 0) {
            Toast.makeText(this, "Заявка не выбрана", Toast.LENGTH_SHORT).show();
            return;
        }

        if (token == null || token.trim().isEmpty()) {
            Toast.makeText(this, "Авторизация не найдена. Войдите заново", Toast.LENGTH_LONG).show();
            ticketIdForAfterPhoto = -1;
            return;
        }

        try {
            File file = createTempFileFromUri(photoUri);
            String mimeType = getContentResolver().getType(photoUri);
            if (mimeType == null) {
                mimeType = "image/jpeg";
            }

            RequestBody photoBody = RequestBody.create(MediaType.parse(mimeType), file);
            MultipartBody.Part photoPart = MultipartBody.Part.createFormData("photo_after", file.getName(), photoBody);
            RequestBody commentBody = RequestBody.create(MediaType.parse("text/plain"), "Работа выполнена");

            progressBar.setVisibility(View.VISIBLE);

            apiService.completeTicket(
                    "Bearer " + token,
                    ticketIdForAfterPhoto,
                    commentBody,
                    photoPart
            ).enqueue(new Callback<CreateTicketResponse>() {
                @Override
                public void onResponse(Call<CreateTicketResponse> call, Response<CreateTicketResponse> response) {
                    progressBar.setVisibility(View.GONE);
                    ticketIdForAfterPhoto = -1;
                    if (response.isSuccessful()) {
                        Toast.makeText(TicketListActivity.this, "Заявка завершена", Toast.LENGTH_LONG).show();
                        loadTickets();
                    } else {
                        Toast.makeText(TicketListActivity.this, "Не удалось завершить заявку", Toast.LENGTH_LONG).show();
                    }
                }

                @Override
                public void onFailure(Call<CreateTicketResponse> call, Throwable t) {
                    progressBar.setVisibility(View.GONE);
                    ticketIdForAfterPhoto = -1;
                    Toast.makeText(TicketListActivity.this, "Ошибка подключения: " + t.getMessage(), Toast.LENGTH_LONG).show();
                }
            });
        } catch (IOException e) {
            Toast.makeText(this, "Не удалось прочитать фото", Toast.LENGTH_LONG).show();
        }
    }

    private void showDeleteConfirmation(Ticket ticket) {
        AlertDialog dialog = new MaterialAlertDialogBuilder(this)
                .setTitle("Удалить заявку из истории?")
                .setMessage("Заявка исчезнет из вашего списка. Это действие нельзя отменить.")
                .setNegativeButton("Отмена", null)
                .setPositiveButton("Удалить", (d, which) -> deleteTicket(ticket.getId()))
                .show();

        dialog.getButton(AlertDialog.BUTTON_POSITIVE).setTextColor(Color.parseColor("#B91C1C"));
    }

    private void deleteTicket(int ticketId) {
        if (token == null || token.trim().isEmpty()) {
            Toast.makeText(this, "Авторизация не найдена. Войдите заново", Toast.LENGTH_LONG).show();
            return;
        }

        progressBar.setVisibility(View.VISIBLE);
        apiService.deleteTicket("Bearer " + token, ticketId).enqueue(new Callback<CreateTicketResponse>() {
            @Override
            public void onResponse(Call<CreateTicketResponse> call, Response<CreateTicketResponse> response) {
                progressBar.setVisibility(View.GONE);
                if (response.isSuccessful()) {
                    Toast.makeText(TicketListActivity.this, "Заявка удалена из истории", Toast.LENGTH_LONG).show();
                    loadTickets();
                } else if (response.code() == 404 || response.code() == 405) {
                    Toast.makeText(TicketListActivity.this, "Сервер не поддерживает удаление заявок", Toast.LENGTH_LONG).show();
                } else {
                    Toast.makeText(TicketListActivity.this, "Не удалось удалить заявку", Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onFailure(Call<CreateTicketResponse> call, Throwable t) {
                progressBar.setVisibility(View.GONE);
                Toast.makeText(TicketListActivity.this, "Ошибка подключения: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    private File createTempFileFromUri(Uri uri) throws IOException {
        InputStream inputStream = getContentResolver().openInputStream(uri);
        if (inputStream == null) {
            throw new IOException("Не удалось открыть фото");
        }
        File file = new File(getCacheDir(), "after_photo_" + System.currentTimeMillis() + ".jpg");
        FileOutputStream outputStream = new FileOutputStream(file);

        byte[] buffer = new byte[4096];
        int read;
        while ((read = inputStream.read(buffer)) != -1) {
            outputStream.write(buffer, 0, read);
        }

        outputStream.flush();
        outputStream.close();
        inputStream.close();

        return file;
    }

    private int dp(int value) {
        return (int) (value * getResources().getDisplayMetrics().density);
    }
}
