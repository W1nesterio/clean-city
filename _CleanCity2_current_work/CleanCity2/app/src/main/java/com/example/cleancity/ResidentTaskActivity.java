package com.example.cleancity;

import android.app.AlertDialog;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.res.ColorStateList;
import android.graphics.Color;
import android.net.Uri;
import android.os.Bundle;
import android.provider.MediaStore;
import android.text.InputType;
import android.view.View;
import android.widget.EditText;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.content.FileProvider;

import com.example.cleancity.api.ApiClient;
import com.example.cleancity.api.ApiService;
import com.example.cleancity.models.CreateTicketResponse;
import com.example.cleancity.models.SimpleResponse;
import com.example.cleancity.models.Ticket;
import com.example.cleancity.models.TicketStatusRequest;
import com.example.cleancity.models.TicketsResponse;
import com.example.cleancity.ui.AppUi;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.card.MaterialCardView;

import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.util.ArrayList;
import java.util.List;

import okhttp3.MediaType;
import okhttp3.MultipartBody;
import okhttp3.RequestBody;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class ResidentTaskActivity extends AppCompatActivity {

    private static final int PICK_AFTER_PHOTO_REQUEST = 4001;
    private static final int TAKE_AFTER_PHOTO_REQUEST = 4002;

    private LinearLayout tasksContainer;
    private ProgressBar progressBar;
    private TextView navHomeTextView;
    private TextView navCreateTextView;
    private TextView navTasksTextView;
    private TextView navHistoryTextView;
    private TextView navSettingsTextView;
    private MaterialButton tabAvailable;
    private MaterialButton tabMine;

    private ApiService apiService;
    private String token;
    private String currentTab = "available"; // "available" or "mine"
    private int ticketIdForAfterPhoto = -1;
    private String completionComment = "Работа выполнена";
    private File afterCameraFile;
    private Uri afterCameraUri;
    private final List<Ticket> loadedTickets = new ArrayList<>();

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        AppUi.applyTheme(this);
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_resident_tasks);

        tasksContainer = findViewById(R.id.residentTasksContainer);
        progressBar = findViewById(R.id.residentTasksProgressBar);
        tabAvailable = findViewById(R.id.tabAvailable);
        tabMine = findViewById(R.id.tabMine);
        navHomeTextView = findViewById(R.id.navHomeTextView);
        navCreateTextView = findViewById(R.id.navTasksTextView);
        navTasksTextView = findViewById(R.id.navMapTextView);
        navHistoryTextView = findViewById(R.id.navRouteTextView);
        navSettingsTextView = findViewById(R.id.navProfileTextView);

        findViewById(R.id.residentTasksBackButton).setOnClickListener(v -> finish());

        SharedPreferences prefs = getSharedPreferences("auth", MODE_PRIVATE);
        token = prefs.getString("token", "");
        String serverUrl = prefs.getString("server_url", ApiClient.DEFAULT_BASE_URL);
        apiService = ApiClient.getClient(serverUrl).create(ApiService.class);

        tabAvailable.setOnClickListener(v -> { currentTab = "available"; updateTabs(); loadTasks(); });
        tabMine.setOnClickListener(v -> { currentTab = "mine"; updateTabs(); loadTasks(); });

        setupBottomNav();
        updateTabs();
        loadTasks();
    }

    private void updateTabs() {
        boolean isAvail = "available".equals(currentTab);
        tabAvailable.setBackgroundTintList(ColorStateList.valueOf(Color.parseColor(isAvail ? "#0b653a" : "#E7EFEA")));
        tabAvailable.setTextColor(Color.parseColor(isAvail ? "#FFFFFF" : "#374151"));
        tabMine.setBackgroundTintList(ColorStateList.valueOf(Color.parseColor(isAvail ? "#E7EFEA" : "#0b653a")));
        tabMine.setTextColor(Color.parseColor(isAvail ? "#374151" : "#FFFFFF"));
    }

    private void loadTasks() {
        progressBar.setVisibility(View.VISIBLE);
        tasksContainer.removeAllViews();
        loadedTickets.clear();

        if ("available".equals(currentTab)) {
            apiService.getResidentAvailableTasks("Bearer " + token)
                    .enqueue(new Callback<TicketsResponse>() {
                        @Override
                        public void onResponse(Call<TicketsResponse> call, Response<TicketsResponse> response) {
                            progressBar.setVisibility(View.GONE);
                            if (response.isSuccessful() && response.body() != null && response.body().getTickets() != null) {
                                loadedTickets.addAll(response.body().getTickets());
                            }
                            showTasks(loadedTickets, true);
                        }
                        @Override
                        public void onFailure(Call<TicketsResponse> call, Throwable t) {
                            progressBar.setVisibility(View.GONE);
                            Toast.makeText(ResidentTaskActivity.this, "Ошибка загрузки", Toast.LENGTH_SHORT).show();
                        }
                    });
        } else {
            apiService.getMyTickets("Bearer " + token)
                    .enqueue(new Callback<TicketsResponse>() {
                        @Override
                        public void onResponse(Call<TicketsResponse> call, Response<TicketsResponse> response) {
                            progressBar.setVisibility(View.GONE);
                            if (response.isSuccessful() && response.body() != null && response.body().getTickets() != null) {
                                for (Ticket t : response.body().getTickets()) {
                                    String s = t.getStatus();
                                    if ("accepted".equals(s) || "in_progress".equals(s) ||
                                            "problem".equals(s) || "postponed".equals(s) ||
                                            "completed".equals(s)) {
                                        loadedTickets.add(t);
                                    }
                                }
                            }
                            showTasks(loadedTickets, false);
                        }
                        @Override
                        public void onFailure(Call<TicketsResponse> call, Throwable t) {
                            progressBar.setVisibility(View.GONE);
                            Toast.makeText(ResidentTaskActivity.this, "Ошибка загрузки", Toast.LENGTH_SHORT).show();
                        }
                    });
        }
    }

    private void showTasks(List<Ticket> tickets, boolean showAcceptButton) {
        tasksContainer.removeAllViews();

        if (tickets.isEmpty()) {
            MaterialCardView card = new MaterialCardView(this);
            card.setRadius(dp(20));
            card.setCardElevation(dp(2));
            card.setCardBackgroundColor(Color.WHITE);
            LinearLayout.LayoutParams p = new LinearLayout.LayoutParams(
                    LinearLayout.LayoutParams.MATCH_PARENT, LinearLayout.LayoutParams.WRAP_CONTENT);
            p.setMargins(0, 0, 0, dp(14));
            card.setLayoutParams(p);
            card.setContentPadding(dp(20), dp(20), dp(20), dp(20));

            TextView empty = new TextView(this);
            empty.setText(showAcceptButton ? "Доступных заданий пока нет" : "Принятых заданий нет");
            empty.setTextSize(16);
            empty.setTextColor(Color.parseColor("#6B7280"));
            card.addView(empty);
            tasksContainer.addView(card);
            return;
        }

        for (Ticket ticket : tickets) {
            tasksContainer.addView(buildTaskCard(ticket, showAcceptButton));
        }
    }

    private View buildTaskCard(Ticket ticket, boolean showAcceptButton) {
        MaterialCardView card = new MaterialCardView(this);
        LinearLayout.LayoutParams cardParams = new LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT, LinearLayout.LayoutParams.WRAP_CONTENT);
        cardParams.setMargins(0, 0, 0, dp(14));
        card.setLayoutParams(cardParams);
        card.setRadius(dp(22));
        card.setCardElevation(dp(4));
        card.setCardBackgroundColor(Color.WHITE);
        card.setContentPadding(dp(18), dp(18), dp(18), dp(18));

        LinearLayout content = new LinearLayout(this);
        content.setOrientation(LinearLayout.VERTICAL);

        // Header row
        LinearLayout head = new LinearLayout(this);
        head.setOrientation(LinearLayout.HORIZONTAL);
        head.setGravity(android.view.Gravity.CENTER_VERTICAL);

        TextView title = new TextView(this);
        String catName = ticket.getCategory() != null ? ticket.getCategory().getName() : "Задание";
        title.setText("№" + ticket.getId() + " · " + catName);
        title.setTextColor(Color.parseColor("#1F2933"));
        title.setTextSize(17);
        title.setTypeface(null, android.graphics.Typeface.BOLD);
        title.setLayoutParams(new LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1));
        head.addView(title);

        // Points badge
        TextView pointsBadge = new TextView(this);
        pointsBadge.setText("+10 баллов");
        pointsBadge.setTextColor(Color.parseColor("#166534"));
        pointsBadge.setTextSize(11);
        pointsBadge.setTypeface(null, android.graphics.Typeface.BOLD);
        pointsBadge.setPadding(dp(10), dp(5), dp(10), dp(5));
        pointsBadge.setBackgroundColor(Color.parseColor("#DCFCE7"));
        head.addView(pointsBadge);

        content.addView(head);

        // Status (for mine tab)
        if (!showAcceptButton) {
            TextView statusBadge = new TextView(this);
            statusBadge.setText(ticket.getStatusLabel());
            statusBadge.setTextColor(Color.parseColor(statusColor(ticket.getStatus())));
            statusBadge.setTextSize(12);
            statusBadge.setTypeface(null, android.graphics.Typeface.BOLD);
            statusBadge.setPadding(dp(10), dp(4), dp(10), dp(4));
            statusBadge.setBackgroundColor(Color.parseColor(statusBackground(ticket.getStatus())));
            LinearLayout.LayoutParams statusParams = new LinearLayout.LayoutParams(
                    LinearLayout.LayoutParams.WRAP_CONTENT, LinearLayout.LayoutParams.WRAP_CONTENT);
            statusParams.setMargins(0, dp(8), 0, 0);
            statusBadge.setLayoutParams(statusParams);
            content.addView(statusBadge);
        }

        // Location
        TextView location = new TextView(this);
        String addr = ticket.getAddressText();
        location.setText(addr != null && !addr.isEmpty() ? addr : "Место на карте");
        location.setTextColor(Color.parseColor("#4B5563"));
        location.setTextSize(13);
        location.setPadding(0, dp(8), 0, 0);
        content.addView(location);

        // Description
        String desc = ticket.getDescription() != null && !ticket.getDescription().trim().isEmpty()
                ? ticket.getDescription().trim() : "";
        if (!desc.isEmpty()) {
            TextView description = new TextView(this);
            description.setText(desc);
            description.setTextColor(Color.parseColor("#6B7280"));
            description.setTextSize(13);
            description.setMaxLines(3);
            description.setPadding(0, dp(6), 0, 0);
            content.addView(description);
        }

        // Date
        TextView date = new TextView(this);
        date.setText("Создана: " + (ticket.getCreatedAt() != null ? ticket.getCreatedAt() : "—"));
        date.setTextColor(Color.parseColor("#9CA3AF"));
        date.setTextSize(11);
        date.setPadding(0, dp(6), 0, 0);
        content.addView(date);

        // Actions
        LinearLayout actions = new LinearLayout(this);
        actions.setOrientation(LinearLayout.VERTICAL);
        actions.setPadding(0, dp(10), 0, 0);

        if (showAcceptButton) {
            MaterialButton acceptBtn = makeButton("Взять задачу", "#0b653a");
            acceptBtn.setOnClickListener(v -> acceptTask(ticket.getId()));
            actions.addView(acceptBtn);
        } else {
            String status = ticket.getStatus();
            if ("accepted".equals(status) || "in_progress".equals(status) ||
                    "problem".equals(status) || "postponed".equals(status)) {
                MaterialButton completeBtn = makeButton("Завершить с фото", "#166534");
                completeBtn.setOnClickListener(v -> askCompletionComment(ticket.getId()));
                actions.addView(completeBtn);

                if ("accepted".equals(status)) {
                    MaterialButton startBtn = makeButton("Начать выполнение", "#1D4ED8");
                    startBtn.setOnClickListener(v -> changeStatus(ticket.getId(), "in_progress", "Начато выполнение"));
                    LinearLayout.LayoutParams sp = new LinearLayout.LayoutParams(
                            LinearLayout.LayoutParams.MATCH_PARENT, dp(50));
                    sp.setMargins(0, dp(6), 0, 0);
                    startBtn.setLayoutParams(sp);
                    actions.addView(startBtn);
                }
            } else if ("completed".equals(status)) {
                TextView done = new TextView(this);
                done.setText("✓ Задание выполнено — баллы начислены");
                done.setTextColor(Color.parseColor("#166534"));
                done.setTextSize(13);
                done.setTypeface(null, android.graphics.Typeface.BOLD);
                done.setPadding(0, dp(6), 0, 0);
                actions.addView(done);
            }
        }

        content.addView(actions);
        card.addView(content);
        return card;
    }

    private void acceptTask(int ticketId) {
        progressBar.setVisibility(View.VISIBLE);
        apiService.residentAcceptTicket("Bearer " + token, ticketId)
                .enqueue(new Callback<SimpleResponse>() {
                    @Override
                    public void onResponse(Call<SimpleResponse> call, Response<SimpleResponse> response) {
                        progressBar.setVisibility(View.GONE);
                        if (response.isSuccessful()) {
                            Toast.makeText(ResidentTaskActivity.this,
                                    "Задание принято! Перейдите на вкладку «Мои задания»",
                                    Toast.LENGTH_LONG).show();
                            loadTasks();
                        } else if (response.code() == 409) {
                            Toast.makeText(ResidentTaskActivity.this,
                                    "Задание уже занято", Toast.LENGTH_SHORT).show();
                        } else {
                            Toast.makeText(ResidentTaskActivity.this,
                                    "Не удалось принять задание", Toast.LENGTH_SHORT).show();
                        }
                    }
                    @Override
                    public void onFailure(Call<SimpleResponse> call, Throwable t) {
                        progressBar.setVisibility(View.GONE);
                        Toast.makeText(ResidentTaskActivity.this, "Ошибка соединения", Toast.LENGTH_SHORT).show();
                    }
                });
    }

    private void changeStatus(int ticketId, String status, String comment) {
        progressBar.setVisibility(View.VISIBLE);
        apiService.changeStatus("Bearer " + token, ticketId, new TicketStatusRequest(status, comment))
                .enqueue(new Callback<CreateTicketResponse>() {
                    @Override
                    public void onResponse(Call<CreateTicketResponse> call, Response<CreateTicketResponse> response) {
                        progressBar.setVisibility(View.GONE);
                        if (response.isSuccessful()) {
                            loadTasks();
                        } else {
                            Toast.makeText(ResidentTaskActivity.this, "Не удалось обновить задачу", Toast.LENGTH_SHORT).show();
                        }
                    }
                    @Override
                    public void onFailure(Call<CreateTicketResponse> call, Throwable t) {
                        progressBar.setVisibility(View.GONE);
                    }
                });
    }

    private void askCompletionComment(int ticketId) {
        ticketIdForAfterPhoto = ticketId;

        LinearLayout layout = new LinearLayout(this);
        layout.setOrientation(LinearLayout.VERTICAL);
        layout.setPadding(dp(22), dp(20), dp(22), dp(14));

        TextView title = new TextView(this);
        title.setText("Завершить задание");
        title.setTextColor(Color.parseColor("#1F2933"));
        title.setTextSize(20);
        title.setTypeface(null, android.graphics.Typeface.BOLD);
        layout.addView(title);

        TextView subtitle = new TextView(this);
        subtitle.setText("Добавьте фото результата. После подтверждения вам начислятся баллы.");
        subtitle.setTextColor(Color.parseColor("#6B7280"));
        subtitle.setTextSize(13);
        subtitle.setPadding(0, dp(6), 0, dp(12));
        layout.addView(subtitle);

        EditText input = new EditText(this);
        input.setHint("Комментарий (необязательно)");
        input.setInputType(InputType.TYPE_CLASS_TEXT | InputType.TYPE_TEXT_FLAG_MULTI_LINE);
        input.setMinLines(2);
        input.setMaxLines(4);
        input.setPadding(dp(12), dp(10), dp(12), dp(10));
        LinearLayout.LayoutParams inputParams = new LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT, LinearLayout.LayoutParams.WRAP_CONTENT);
        inputParams.setMargins(0, 0, 0, dp(12));
        input.setLayoutParams(inputParams);
        input.setBackgroundResource(R.drawable.bg_completion_option);
        layout.addView(input);

        LinearLayout actions = new LinearLayout(this);
        actions.setOrientation(LinearLayout.HORIZONTAL);
        actions.setPadding(0, dp(10), 0, 0);

        AlertDialog dialog = new AlertDialog.Builder(this).create();

        MaterialButton cameraButton = makeDialogButton("Сделать фото", "#0b653a");
        cameraButton.setOnClickListener(v -> {
            String text = input.getText() != null ? input.getText().toString().trim() : "";
            completionComment = text.isEmpty() ? "Задание выполнено жителем" : text;
            dialog.dismiss();
            openAfterPhotoCamera(ticketId);
        });
        LinearLayout.LayoutParams cp = new LinearLayout.LayoutParams(0, dp(52), 1);
        cp.setMargins(0, 0, dp(8), 0);
        actions.addView(cameraButton, cp);

        MaterialButton galleryButton = makeDialogButton("Из галереи", "#374151");
        galleryButton.setOnClickListener(v -> {
            String text = input.getText() != null ? input.getText().toString().trim() : "";
            completionComment = text.isEmpty() ? "Задание выполнено жителем" : text;
            dialog.dismiss();
            openAfterPhotoGallery(ticketId);
        });
        LinearLayout.LayoutParams gp = new LinearLayout.LayoutParams(0, dp(52), 1);
        gp.setMargins(dp(8), 0, 0, 0);
        actions.addView(galleryButton, gp);

        layout.addView(actions);

        TextView cancel = new TextView(this);
        cancel.setText("Отмена");
        cancel.setTextColor(Color.parseColor("#6B7280"));
        cancel.setTextSize(14);
        cancel.setGravity(android.view.Gravity.CENTER);
        cancel.setPadding(0, dp(14), 0, dp(4));
        cancel.setOnClickListener(v -> dialog.dismiss());
        layout.addView(cancel);

        dialog.setView(layout);
        dialog.show();
    }

    private void openAfterPhotoCamera(int ticketId) {
        ticketIdForAfterPhoto = ticketId;
        try {
            afterCameraFile = createImageFile("resident_after_");
            afterCameraUri = FileProvider.getUriForFile(this,
                    getPackageName() + ".fileprovider", afterCameraFile);
            Intent intent = new Intent(MediaStore.ACTION_IMAGE_CAPTURE);
            intent.putExtra(MediaStore.EXTRA_OUTPUT, afterCameraUri);
            intent.addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION | Intent.FLAG_GRANT_WRITE_URI_PERMISSION);
            if (intent.resolveActivity(getPackageManager()) != null) {
                startActivityForResult(intent, TAKE_AFTER_PHOTO_REQUEST);
            } else {
                Toast.makeText(this, "Камера недоступна", Toast.LENGTH_SHORT).show();
            }
        } catch (Exception e) {
            Toast.makeText(this, "Не удалось открыть камеру", Toast.LENGTH_SHORT).show();
        }
    }

    private void openAfterPhotoGallery(int ticketId) {
        ticketIdForAfterPhoto = ticketId;
        Intent intent = new Intent(Intent.ACTION_GET_CONTENT);
        intent.setType("image/*");
        startActivityForResult(Intent.createChooser(intent, "Фото результата"), PICK_AFTER_PHOTO_REQUEST);
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, @Nullable Intent data) {
        super.onActivityResult(requestCode, resultCode, data);
        if (requestCode == PICK_AFTER_PHOTO_REQUEST && resultCode == RESULT_OK && data != null && data.getData() != null) {
            completeTicketWithPhoto(data.getData());
        } else if (requestCode == TAKE_AFTER_PHOTO_REQUEST && resultCode == RESULT_OK && afterCameraUri != null) {
            completeTicketWithPhoto(afterCameraUri);
        } else if (requestCode == TAKE_AFTER_PHOTO_REQUEST) {
            afterCameraUri = null;
            afterCameraFile = null;
        }
    }

    private void completeTicketWithPhoto(Uri photoUri) {
        if (ticketIdForAfterPhoto <= 0) return;

        try {
            File file = createTempFileFromUri(photoUri);
            String mimeType = getContentResolver().getType(photoUri);
            if (mimeType == null) mimeType = "image/jpeg";

            RequestBody photoBody = RequestBody.create(MediaType.parse(mimeType), file);
            MultipartBody.Part photoPart = MultipartBody.Part.createFormData("photo_after", file.getName(), photoBody);
            RequestBody commentBody = RequestBody.create(MediaType.parse("text/plain"), completionComment);

            progressBar.setVisibility(View.VISIBLE);
            final int tId = ticketIdForAfterPhoto;

            apiService.completeTicket("Bearer " + token, tId, commentBody, photoPart)
                    .enqueue(new Callback<CreateTicketResponse>() {
                        @Override
                        public void onResponse(Call<CreateTicketResponse> call, Response<CreateTicketResponse> response) {
                            progressBar.setVisibility(View.GONE);
                            if (response.isSuccessful()) {
                                Toast.makeText(ResidentTaskActivity.this,
                                        "Задание завершено! Баллы начислены.", Toast.LENGTH_LONG).show();
                                currentTab = "mine";
                                updateTabs();
                                loadTasks();
                            } else {
                                Toast.makeText(ResidentTaskActivity.this,
                                        "Не удалось завершить задание", Toast.LENGTH_LONG).show();
                            }
                            afterCameraUri = null;
                            afterCameraFile = null;
                        }
                        @Override
                        public void onFailure(Call<CreateTicketResponse> call, Throwable t) {
                            progressBar.setVisibility(View.GONE);
                            Toast.makeText(ResidentTaskActivity.this, "Ошибка соединения", Toast.LENGTH_SHORT).show();
                        }
                    });
        } catch (Exception e) {
            Toast.makeText(this, "Ошибка обработки фото", Toast.LENGTH_SHORT).show();
        }
    }

    private File createTempFileFromUri(Uri uri) throws IOException {
        InputStream inputStream = getContentResolver().openInputStream(uri);
        if (inputStream == null) throw new IOException("Cannot open stream");
        File tempFile = File.createTempFile("upload_", ".jpg", getCacheDir());
        FileOutputStream out = new FileOutputStream(tempFile);
        byte[] buffer = new byte[4096];
        int read;
        while ((read = inputStream.read(buffer)) != -1) out.write(buffer, 0, read);
        out.close();
        inputStream.close();
        return tempFile;
    }

    private File createImageFile(String prefix) throws IOException {
        File storageDir = getExternalFilesDir(null);
        return File.createTempFile(prefix + System.currentTimeMillis(), ".jpg", storageDir);
    }

    private void setupBottomNav() {
        setNavSelected(navHomeTextView, false);
        setNavSelected(navCreateTextView, false);
        setNavSelected(navTasksTextView, true); // "Задачи" active
        setNavSelected(navHistoryTextView, false);
        setNavSelected(navSettingsTextView, false);

        navHomeTextView.setText(AppUi.t(this, "Главная", "Галоўная"));
        navCreateTextView.setText(AppUi.t(this, "Заявка", "Заяўка"));
        navTasksTextView.setText(AppUi.t(this, "Задачи", "Задачы"));
        navHistoryTextView.setText(AppUi.t(this, "История", "Гісторыя"));
        navSettingsTextView.setText(AppUi.t(this, "Настройки", "Налады"));

        navHomeTextView.setVisibility(View.VISIBLE);
        navCreateTextView.setVisibility(View.VISIBLE);
        navTasksTextView.setVisibility(View.VISIBLE);
        navHistoryTextView.setVisibility(View.VISIBLE);
        navSettingsTextView.setVisibility(View.VISIBLE);

        navHomeTextView.setOnClickListener(v -> {
            AppUi.feedback(this, v);
            Intent intent = new Intent(this, HomeActivity.class);
            intent.setFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP | Intent.FLAG_ACTIVITY_SINGLE_TOP);
            startActivity(intent);
        });
        navCreateTextView.setOnClickListener(v -> { AppUi.feedback(this, v); startActivity(new Intent(this, CreateTicketActivity.class)); });
        navTasksTextView.setOnClickListener(v -> { AppUi.feedback(this, v); });
        navHistoryTextView.setOnClickListener(v -> {
            AppUi.feedback(this, v);
            Intent i = new Intent(this, TicketListActivity.class);
            i.putExtra("mode", "my");
            startActivity(i);
        });
        navSettingsTextView.setOnClickListener(v -> { AppUi.feedback(this, v); startActivity(new Intent(this, SettingsActivity.class)); });
    }

    private void setNavSelected(TextView item, boolean selected) {
        item.setBackgroundResource(selected ? R.drawable.bg_bottom_nav_selected : R.drawable.bg_bottom_nav_plain);
        item.setTextColor(selected ? getColor(R.color.white) : getColor(R.color.text_gray));
    }

    private MaterialButton makeButton(String text, String color) {
        MaterialButton button = new MaterialButton(this);
        button.setText(text);
        button.setTextSize(14);
        button.setAllCaps(false);
        button.setCornerRadius(dp(14));
        button.setBackgroundTintList(ColorStateList.valueOf(Color.parseColor(color)));
        button.setTextColor(Color.WHITE);
        LinearLayout.LayoutParams params = new LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT, dp(50));
        params.setMargins(0, dp(8), 0, 0);
        button.setLayoutParams(params);
        return button;
    }

    private MaterialButton makeDialogButton(String text, String color) {
        MaterialButton button = new MaterialButton(this);
        button.setText(text);
        button.setTextSize(14);
        button.setTextColor(Color.WHITE);
        button.setAllCaps(false);
        button.setCornerRadius(dp(17));
        button.setBackgroundTintList(ColorStateList.valueOf(Color.parseColor(color)));
        return button;
    }

    private String statusColor(String status) {
        if (status == null) return "#146C43";
        switch (status) {
            case "accepted": return "#5B21B6";
            case "in_progress": return "#1D4ED8";
            case "completed": return "#166534";
            case "problem": return "#991B1B";
            case "postponed": return "#92400E";
            default: return "#146C43";
        }
    }

    private String statusBackground(String status) {
        if (status == null) return "#DCFCE7";
        switch (status) {
            case "accepted": return "#EDE9FE";
            case "in_progress": return "#DBEAFE";
            case "completed": return "#DCFCE7";
            case "problem": return "#FEE2E2";
            case "postponed": return "#FEF3C7";
            default: return "#F0FDF4";
        }
    }

    private int dp(int value) {
        return Math.round(value * getResources().getDisplayMetrics().density);
    }
}
