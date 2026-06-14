package com.example.cleancity;

import android.app.AlertDialog;
import android.content.Intent;
import android.content.SharedPreferences;
import android.database.Cursor;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.net.Uri;
import android.os.Bundle;
import android.provider.MediaStore;
import android.provider.OpenableColumns;
import android.view.View;
import android.widget.FrameLayout;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;

import com.example.cleancity.api.ApiClient;
import com.example.cleancity.api.ApiService;
import com.example.cleancity.models.LoginResponse;
import com.example.cleancity.models.Ticket;
import com.example.cleancity.models.TicketsResponse;
import com.example.cleancity.models.User;
import com.example.cleancity.ui.CircleImageView;
import com.example.cleancity.ui.AppUi;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.card.MaterialCardView;

import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.util.ArrayList;
import java.util.List;

import okhttp3.MediaType;
import okhttp3.MultipartBody;
import okhttp3.RequestBody;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class WorkerProfileActivity extends AppCompatActivity {

    private static final int PICK_AVATAR_REQUEST = 4101;
    private static final int TAKE_AVATAR_PHOTO_REQUEST = 4102;

    private FrameLayout avatarContainer;
    private CircleImageView avatarImageView;
    private TextView avatarInitialsTextView;
    private TextView profileNameTextView;
    private TextView organizationTextView;
    private ProgressBar profileProgressBar;
    private TextView newCountTextView;
    private TextView inProgressCountTextView;
    private TextView completedCountTextView;
    private TextView totalCountTextView;
    private LinearLayout completedTasksContainer;
    private MaterialButton backButton;
    private MaterialButton openTasksButton;
    private MaterialButton logoutProfileButton;
    private TextView navHomeTextView;
    private TextView navTasksTextView;
    private TextView navRouteTextView;
    private TextView navMapTextView;
    private TextView navProfileTextView;

    private ApiService apiService;
    private String token;
    private Uri selectedAvatarUri;
    private File selectedAvatarFile;
    private User currentUser;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        AppUi.applyTheme(this);
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_worker_profile);
        AppUi.applyAll(this, "Профиль", "Профіль");

        backButton = findViewById(R.id.profileBackButton);
        avatarContainer = findViewById(R.id.avatarContainer);
        avatarImageView = findViewById(R.id.avatarImageView);
        avatarInitialsTextView = findViewById(R.id.avatarInitialsTextView);
        profileNameTextView = findViewById(R.id.profileNameTextView);
        organizationTextView = findViewById(R.id.organizationTextView);
        profileProgressBar = findViewById(R.id.profileProgressBar);
        newCountTextView = findViewById(R.id.newCountTextView);
        inProgressCountTextView = findViewById(R.id.inProgressCountTextView);
        completedCountTextView = findViewById(R.id.completedCountTextView);
        totalCountTextView = findViewById(R.id.totalCountTextView);
        completedTasksContainer = findViewById(R.id.completedTasksContainer);
        openTasksButton = findViewById(R.id.openTasksButton);
        logoutProfileButton = findViewById(R.id.logoutProfileButton);
        navHomeTextView = findViewById(R.id.navHomeTextView);
        navTasksTextView = findViewById(R.id.navTasksTextView);
        navRouteTextView = findViewById(R.id.navRouteTextView);
        navMapTextView = findViewById(R.id.navMapTextView);
        navProfileTextView = findViewById(R.id.navProfileTextView);

        SharedPreferences preferences = getSharedPreferences("auth", MODE_PRIVATE);
        token = preferences.getString("token", "");
        String serverUrl = preferences.getString("server_url", ApiClient.DEFAULT_BASE_URL);
        apiService = ApiClient.getClient(serverUrl).create(ApiService.class);

        backButton.setOnClickListener(v -> finish());
        avatarContainer.setOnClickListener(v -> showAvatarMenu());
        openTasksButton.setOnClickListener(v -> openTasks());
        logoutProfileButton.setOnClickListener(v -> logout());
        setupBottomNav("profile");

        loadProfile();
        loadTasks();
    }

    private void setupBottomNav(String active) {
        navProfileTextView.setVisibility(View.VISIBLE);
        navProfileTextView.setText(AppUi.t(this, "Настройки", "Налады"));
        navHomeTextView.setText(AppUi.t(this, "Главная", "Галоўная"));
        navTasksTextView.setText(AppUi.t(this, "Задачи", "Задачы"));
        navRouteTextView.setText(AppUi.t(this, "Маршрут", "Маршрут"));
        navMapTextView.setText(AppUi.t(this, "Карта", "Карта"));

        setNavSelected(navHomeTextView, "home".equals(active));
        setNavSelected(navTasksTextView, "tasks".equals(active));
        setNavSelected(navRouteTextView, "route".equals(active));
        setNavSelected(navMapTextView, "map".equals(active));
        setNavSelected(navProfileTextView, "settings".equals(active));
        navHomeTextView.setOnClickListener(v -> openHome());
        navTasksTextView.setOnClickListener(v -> openTasks());
        navRouteTextView.setOnClickListener(v -> startActivity(new Intent(this, WorkerRouteActivity.class)));
        navMapTextView.setOnClickListener(v -> startActivity(new Intent(this, WorkerMapActivity.class)));
        navProfileTextView.setOnClickListener(v -> startActivity(new Intent(this, SettingsActivity.class)));
    }

    private void setNavSelected(TextView item, boolean selected) {
        item.setBackgroundResource(selected ? R.drawable.bg_bottom_nav_selected : R.drawable.bg_bottom_nav_plain);
        item.setTextColor(selected ? getColor(R.color.white) : getColor(R.color.text_gray));
    }

    private void openHome() {
        Intent intent = new Intent(this, HomeActivity.class);
        intent.setFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP | Intent.FLAG_ACTIVITY_SINGLE_TOP);
        startActivity(intent);
    }

    private void loadProfile() {
        setLoading(true);
        apiService.getProfile("Bearer " + token).enqueue(new Callback<LoginResponse>() {
            @Override
            public void onResponse(Call<LoginResponse> call, Response<LoginResponse> response) {
                setLoading(false);
                if (response.isSuccessful() && response.body() != null && response.body().getUser() != null) {
                    currentUser = response.body().getUser();
                    saveUserToPreferences(currentUser);
                    fillProfile(currentUser);
                } else {
                    Toast.makeText(WorkerProfileActivity.this, "Не удалось загрузить профиль", Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onFailure(Call<LoginResponse> call, Throwable t) {
                setLoading(false);
                Toast.makeText(WorkerProfileActivity.this, "Ошибка подключения: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    private void fillProfile(User user) {
        String name = safe(user.getName());
        profileNameTextView.setText(name.isEmpty() ? "Сотрудник" : name);
        avatarInitialsTextView.setText(initials(name));

        if (user.getOrganization() != null && user.getOrganization().getName() != null) {
            organizationTextView.setText(user.getOrganization().getName());
        } else if (user.getOrganizationId() != null) {
            organizationTextView.setText("ЖКХ №" + user.getOrganizationId());
        } else {
            organizationTextView.setText("Организация не указана");
        }

        loadAvatar(user.getAvatarUrl());
    }

    private void loadTasks() {
        apiService.getWorkerTickets("Bearer " + token).enqueue(new Callback<TicketsResponse>() {
            @Override
            public void onResponse(Call<TicketsResponse> call, Response<TicketsResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    showTaskSummary(response.body().getTickets());
                }
            }

            @Override
            public void onFailure(Call<TicketsResponse> call, Throwable t) {
                newCountTextView.setText("—");
                inProgressCountTextView.setText("—");
                completedCountTextView.setText("—");
                totalCountTextView.setText("—");
            }
        });
    }

    private void showTaskSummary(List<Ticket> tickets) {
        int newTasks = 0;
        int inProgress = 0;
        int completed = 0;
        int total = 0;
        List<Ticket> completedTickets = new ArrayList<>();

        if (tickets != null) {
            total = tickets.size();
            for (Ticket ticket : tickets) {
                String status = ticket.getStatus();
                if ("completed".equals(status)) {
                    completed++;
                    completedTickets.add(ticket);
                } else if ("assigned".equals(status)) {
                    newTasks++;
                } else if ("in_progress".equals(status) || "problem".equals(status) || "postponed".equals(status)) {
                    inProgress++;
                }
            }
        }

        newCountTextView.setText(String.valueOf(newTasks));
        inProgressCountTextView.setText(String.valueOf(inProgress));
        completedCountTextView.setText(String.valueOf(completed));
        totalCountTextView.setText(String.valueOf(total));
        showCompletedTasks(completedTickets);
    }

    private void showCompletedTasks(List<Ticket> tickets) {
        completedTasksContainer.removeAllViews();

        if (tickets == null || tickets.isEmpty()) {
            TextView empty = new TextView(this);
            empty.setText("Выполненных работ пока нет");
            empty.setTextSize(14);
            empty.setTextColor(android.graphics.Color.parseColor("#6B7280"));
            empty.setPadding(0, dp(6), 0, 0);
            completedTasksContainer.addView(empty);
            AppUi.apply(this);
            return;
        }

        int limit = Math.min(tickets.size(), 7);
        for (int i = 0; i < limit; i++) {
            completedTasksContainer.addView(createCompletedTaskView(tickets.get(i)));
        }
    }

    private View createCompletedTaskView(Ticket ticket) {
        MaterialCardView card = new MaterialCardView(this);
        card.setRadius(dp(16));
        card.setCardElevation(dp(1));
        card.setStrokeColor(android.graphics.Color.parseColor("#E5E7EB"));
        card.setStrokeWidth(dp(1));
        card.setCardBackgroundColor(android.graphics.Color.WHITE);

        LinearLayout.LayoutParams cardParams = new LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
        );
        cardParams.setMargins(0, 0, 0, dp(8));
        card.setLayoutParams(cardParams);

        LinearLayout row = new LinearLayout(this);
        row.setOrientation(LinearLayout.VERTICAL);
        row.setPadding(dp(14), dp(12), dp(14), dp(12));

        String category = ticket.getCategory() != null ? ticket.getCategory().getName() : "Заявка";

        TextView title = new TextView(this);
        title.setText("№" + ticket.getId() + " · " + category);
        title.setTextSize(15);
        title.setTypeface(null, android.graphics.Typeface.BOLD);
        title.setTextColor(android.graphics.Color.parseColor("#1F2933"));
        row.addView(title);

        String address = safe(ticket.getAddressText());
        if (!address.isEmpty()) {
            TextView addressView = new TextView(this);
            addressView.setText(address);
            addressView.setTextSize(12);
            addressView.setTextColor(android.graphics.Color.parseColor("#374151"));
            addressView.setPadding(0, dp(4), 0, 0);
            row.addView(addressView);
        }

        TextView meta = new TextView(this);
        String date = safe(ticket.getClosedAt()).isEmpty() ? safe(ticket.getCreatedAt()) : safe(ticket.getClosedAt());
        meta.setText(date.isEmpty() ? "Выполнена" : date);
        meta.setTextSize(12);
        meta.setTextColor(android.graphics.Color.parseColor("#6B7280"));
        meta.setPadding(0, dp(4), 0, 0);
        row.addView(meta);

        card.addView(row);
        return card;
    }

    private void showAvatarMenu() {
        boolean hasSavedAvatar = currentUser != null && !safe(currentUser.getAvatarUrl()).isEmpty();
        boolean hasPendingAvatar = selectedAvatarUri != null || selectedAvatarFile != null;
        boolean canRemove = hasSavedAvatar || hasPendingAvatar;

        CharSequence[] items = canRemove
                ? new CharSequence[]{"Сделать фото", "Выбрать из галереи", "Удалить фото"}
                : new CharSequence[]{"Сделать фото", "Выбрать из галереи"};

        new AlertDialog.Builder(this)
                .setTitle("Фото профиля")
                .setItems(items, (dialog, which) -> {
                    if (which == 0) {
                        openCamera();
                    } else if (which == 1) {
                        openGallery();
                    } else {
                        removeAvatar();
                    }
                })
                .show();
    }

    private void openGallery() {
        Intent intent = new Intent(Intent.ACTION_GET_CONTENT);
        intent.setType("image/*");
        startActivityForResult(Intent.createChooser(intent, "Выбрать фото"), PICK_AVATAR_REQUEST);
    }

    private void openCamera() {
        Intent intent = new Intent(MediaStore.ACTION_IMAGE_CAPTURE);
        if (intent.resolveActivity(getPackageManager()) == null) {
            Toast.makeText(this, "Камера недоступна", Toast.LENGTH_SHORT).show();
            return;
        }
        startActivityForResult(intent, TAKE_AVATAR_PHOTO_REQUEST);
    }

    private void removeAvatar() {
        selectedAvatarUri = null;
        selectedAvatarFile = null;

        if (currentUser == null || safe(currentUser.getAvatarUrl()).isEmpty()) {
            avatarImageView.setImageDrawable(null);
            avatarImageView.setVisibility(View.GONE);
            avatarInitialsTextView.setVisibility(View.VISIBLE);
            Toast.makeText(this, "Фото убрано", Toast.LENGTH_SHORT).show();
            return;
        }

        new AlertDialog.Builder(this)
                .setTitle("Удалить фото?")
                .setNegativeButton("Отмена", null)
                .setPositiveButton("Удалить", (dialog, which) -> deleteAvatarOnServer())
                .show();
    }

    private void uploadSelectedAvatar() {
        MultipartBody.Part avatarPart;
        try {
            File uploadFile;
            String uploadName = "avatar.jpg";
            String mimeType = "image/jpeg";

            if (selectedAvatarFile != null) {
                uploadFile = selectedAvatarFile;
                uploadName = selectedAvatarFile.getName();
            } else if (selectedAvatarUri != null) {
                uploadFile = createTempFileFromUri(selectedAvatarUri, "avatar_");
                uploadName = fileName(selectedAvatarUri);
                String resolverMimeType = getContentResolver().getType(selectedAvatarUri);
                if (resolverMimeType != null) {
                    mimeType = resolverMimeType;
                }
            } else {
                return;
            }

            RequestBody avatarBody = RequestBody.create(MediaType.parse(mimeType), uploadFile);
            avatarPart = MultipartBody.Part.createFormData("avatar", uploadName, avatarBody);
        } catch (IOException e) {
            Toast.makeText(this, "Не удалось прочитать фото", Toast.LENGTH_LONG).show();
            return;
        }

        setLoading(true);
        apiService.updateProfile("Bearer " + token, avatarPart).enqueue(new Callback<LoginResponse>() {
            @Override
            public void onResponse(Call<LoginResponse> call, Response<LoginResponse> response) {
                setLoading(false);
                if (response.isSuccessful() && response.body() != null && response.body().getUser() != null) {
                    selectedAvatarUri = null;
                    selectedAvatarFile = null;
                    currentUser = response.body().getUser();
                    saveUserToPreferences(currentUser);
                    fillProfile(currentUser);
                    Toast.makeText(WorkerProfileActivity.this, "Фото обновлено", Toast.LENGTH_SHORT).show();
                } else {
                    Toast.makeText(WorkerProfileActivity.this, "Не удалось обновить фото", Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onFailure(Call<LoginResponse> call, Throwable t) {
                setLoading(false);
                Toast.makeText(WorkerProfileActivity.this, "Ошибка подключения: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    private void deleteAvatarOnServer() {
        setLoading(true);
        apiService.deleteAvatar("Bearer " + token).enqueue(new Callback<LoginResponse>() {
            @Override
            public void onResponse(Call<LoginResponse> call, Response<LoginResponse> response) {
                setLoading(false);
                if (response.isSuccessful() && response.body() != null && response.body().getUser() != null) {
                    currentUser = response.body().getUser();
                    saveUserToPreferences(currentUser);
                    fillProfile(currentUser);
                    Toast.makeText(WorkerProfileActivity.this, "Фото удалено", Toast.LENGTH_SHORT).show();
                } else {
                    Toast.makeText(WorkerProfileActivity.this, "Не удалось удалить фото", Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onFailure(Call<LoginResponse> call, Throwable t) {
                setLoading(false);
                Toast.makeText(WorkerProfileActivity.this, "Ошибка подключения: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, @Nullable Intent data) {
        super.onActivityResult(requestCode, resultCode, data);

        if (requestCode == PICK_AVATAR_REQUEST && resultCode == RESULT_OK && data != null && data.getData() != null) {
            selectedAvatarUri = data.getData();
            selectedAvatarFile = null;
            showSelectedAvatar(selectedAvatarUri);
            uploadSelectedAvatar();
            return;
        }

        if (requestCode == TAKE_AVATAR_PHOTO_REQUEST && resultCode == RESULT_OK && data != null) {
            Bundle extras = data.getExtras();
            Bitmap bitmap = extras != null ? (Bitmap) extras.get("data") : null;
            if (bitmap == null) {
                Toast.makeText(this, "Не удалось получить фото", Toast.LENGTH_SHORT).show();
                return;
            }

            try {
                selectedAvatarFile = createTempFileFromBitmap(bitmap);
                selectedAvatarUri = null;
                avatarImageView.setImageBitmap(bitmap);
                avatarImageView.setVisibility(View.VISIBLE);
                avatarInitialsTextView.setVisibility(View.GONE);
                uploadSelectedAvatar();
            } catch (IOException e) {
                Toast.makeText(this, "Не удалось сохранить фото", Toast.LENGTH_SHORT).show();
            }
        }
    }

    private void showSelectedAvatar(Uri uri) {
        try {
            InputStream inputStream = getContentResolver().openInputStream(uri);
            Bitmap bitmap = BitmapFactory.decodeStream(inputStream);
            if (inputStream != null) {
                inputStream.close();
            }
            avatarImageView.setImageBitmap(bitmap);
            avatarImageView.setVisibility(View.VISIBLE);
            avatarInitialsTextView.setVisibility(View.GONE);
        } catch (IOException e) {
            Toast.makeText(this, "Не удалось открыть фото", Toast.LENGTH_SHORT).show();
        }
    }

    private void loadAvatar(String avatarUrl) {
        if (avatarUrl == null || avatarUrl.trim().isEmpty()) {
            avatarImageView.setImageDrawable(null);
            avatarImageView.setVisibility(View.GONE);
            avatarInitialsTextView.setVisibility(View.VISIBLE);
            return;
        }

        new Thread(() -> {
            HttpURLConnection connection = null;
            try {
                URL url = new URL(avatarUrl);
                connection = (HttpURLConnection) url.openConnection();
                connection.setConnectTimeout(8000);
                connection.setReadTimeout(8000);
                Bitmap bitmap = BitmapFactory.decodeStream(connection.getInputStream());
                runOnUiThread(() -> {
                    if (bitmap != null) {
                        avatarImageView.setImageBitmap(bitmap);
                        avatarImageView.setVisibility(View.VISIBLE);
                        avatarInitialsTextView.setVisibility(View.GONE);
                    }
                });
            } catch (Exception ignored) {
                runOnUiThread(() -> {
                    avatarImageView.setVisibility(View.GONE);
                    avatarInitialsTextView.setVisibility(View.VISIBLE);
                });
            } finally {
                if (connection != null) {
                    connection.disconnect();
                }
            }
        }).start();
    }

    private File createTempFileFromUri(Uri uri, String prefix) throws IOException {
        InputStream inputStream = getContentResolver().openInputStream(uri);
        File file = new File(getCacheDir(), prefix + System.currentTimeMillis() + ".jpg");
        FileOutputStream outputStream = new FileOutputStream(file);

        byte[] buffer = new byte[4096];
        int read;
        while (inputStream != null && (read = inputStream.read(buffer)) != -1) {
            outputStream.write(buffer, 0, read);
        }

        outputStream.flush();
        outputStream.close();
        if (inputStream != null) {
            inputStream.close();
        }

        return file;
    }

    private File createTempFileFromBitmap(Bitmap bitmap) throws IOException {
        File file = new File(getCacheDir(), "avatar_camera_" + System.currentTimeMillis() + ".jpg");
        FileOutputStream outputStream = new FileOutputStream(file);
        bitmap.compress(Bitmap.CompressFormat.JPEG, 92, outputStream);
        outputStream.flush();
        outputStream.close();
        return file;
    }

    private String fileName(Uri uri) {
        String result = "avatar.jpg";
        Cursor cursor = getContentResolver().query(uri, null, null, null, null);
        if (cursor != null) {
            try {
                int nameIndex = cursor.getColumnIndex(OpenableColumns.DISPLAY_NAME);
                if (nameIndex >= 0 && cursor.moveToFirst()) {
                    result = cursor.getString(nameIndex);
                }
            } finally {
                cursor.close();
            }
        }
        return result;
    }

    private void openTasks() {
        Intent intent = new Intent(this, TicketListActivity.class);
        intent.putExtra("mode", "worker");
        startActivity(intent);
    }

    private String safe(String value) {
        return value == null ? "" : value.trim();
    }

    private String initials(String name) {
        if (name == null || name.trim().isEmpty()) {
            return "С";
        }

        String[] parts = name.trim().split("\\s+");
        StringBuilder builder = new StringBuilder();
        for (String part : parts) {
            if (!part.isEmpty()) {
                builder.append(part.substring(0, 1).toUpperCase());
            }
            if (builder.length() >= 2) {
                break;
            }
        }
        return builder.length() > 0 ? builder.toString() : "С";
    }

    private void saveUserToPreferences(User user) {
        getSharedPreferences("auth", MODE_PRIVATE).edit()
                .putString("name", safe(user.getName()))
                .putString("email", safe(user.getEmail()))
                .putString("role", safe(user.getRole()))
                .putString("avatar_url", safe(user.getAvatarUrl()))
                .apply();
    }

    private void setLoading(boolean loading) {
        profileProgressBar.setVisibility(loading ? View.VISIBLE : View.GONE);
        avatarContainer.setEnabled(!loading);
        openTasksButton.setEnabled(!loading);
        logoutProfileButton.setEnabled(!loading);
    }

    private void logout() {
        getSharedPreferences("auth", MODE_PRIVATE).edit().clear().apply();
        AppUi.resetAuthMode(this);
        Intent intent = new Intent(this, MainActivity.class);
        intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
        startActivity(intent);
    }

    private int dp(int value) {
        return (int) (value * getResources().getDisplayMetrics().density);
    }
}
