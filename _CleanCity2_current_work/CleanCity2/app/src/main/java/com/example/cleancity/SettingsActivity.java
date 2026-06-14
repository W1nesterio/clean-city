package com.example.cleancity;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.widget.RadioGroup;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.example.cleancity.api.ApiClient;
import com.example.cleancity.api.ApiService;
import com.example.cleancity.models.ChangePasswordRequest;
import com.example.cleancity.models.SimpleResponse;
import com.example.cleancity.ui.AppUi;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class SettingsActivity extends AppCompatActivity {
    private SharedPreferences prefs;
    private String role;
    private String token;
    private ApiService apiService;

    private RadioGroup themeRadioGroup;
    private RadioGroup languageRadioGroup;
    private TextView navHomeTextView;
    private TextView navTasksTextView;
    private TextView navRouteTextView;
    private TextView navMapTextView;
    private TextView navSettingsTextView;
    private TextInputEditText currentPasswordInput;
    private TextInputEditText newPasswordInput;
    private TextInputEditText confirmPasswordInput;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        AppUi.applyTheme(this);
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_settings);

        prefs = AppUi.prefs(this);
        SharedPreferences authPrefs = getSharedPreferences("auth", MODE_PRIVATE);
        role = authPrefs.getString("role", "resident");
        token = authPrefs.getString("token", "");

        String serverUrl = authPrefs.getString("server_url", ApiClient.DEFAULT_BASE_URL);
        apiService = ApiClient.getClient(serverUrl).create(ApiService.class);

        MaterialButton backButton = findViewById(R.id.settingsBackButton);
        themeRadioGroup = findViewById(R.id.themeRadioGroup);
        languageRadioGroup = findViewById(R.id.languageRadioGroup);
        navHomeTextView = findViewById(R.id.navHomeTextView);
        navTasksTextView = findViewById(R.id.navTasksTextView);
        navRouteTextView = findViewById(R.id.navRouteTextView);
        navMapTextView = findViewById(R.id.navMapTextView);
        navSettingsTextView = findViewById(R.id.navSettingsTextView);
        currentPasswordInput = findViewById(R.id.currentPasswordInput);
        newPasswordInput = findViewById(R.id.newPasswordInput);
        confirmPasswordInput = findViewById(R.id.confirmPasswordInput);
        MaterialButton changePasswordButton = findViewById(R.id.changePasswordButton);

        backButton.setOnClickListener(v -> finish());
        changePasswordButton.setOnClickListener(v -> handleChangePassword());
        fillProfileInfo(authPrefs);
        bindValues();
        bindActions();
        setupBottomNav();
        applyLanguage();
        AppUi.applyAll(this, "Настройки", "Налады");
    }

    private void fillProfileInfo(SharedPreferences authPrefs) {
        String name = authPrefs.getString("name", "");
        String email = authPrefs.getString("email", "");
        String roleLabel = getRoleLabel(role);

        TextView nameView = findViewById(R.id.profileNameTextView);
        TextView emailView = findViewById(R.id.profileEmailTextView);
        TextView roleView = findViewById(R.id.profileRoleTextView);

        if (nameView != null) nameView.setText(name.isEmpty() ? "—" : name);
        if (emailView != null) emailView.setText(email.isEmpty() ? "—" : email);
        if (roleView != null) roleView.setText(roleLabel);
    }

    private String getRoleLabel(String role) {
        switch (role) {
            case "worker": return "Исполнитель";
            case "admin": return "Администратор";
            case "org_admin": return "Администратор ЖКХ";
            case "super_admin": return "Суперадминистратор";
            default: return "Житель";
        }
    }

    private void handleChangePassword() {
        String current = currentPasswordInput.getText() != null ? currentPasswordInput.getText().toString().trim() : "";
        String newPass = newPasswordInput.getText() != null ? newPasswordInput.getText().toString().trim() : "";
        String confirm = confirmPasswordInput.getText() != null ? confirmPasswordInput.getText().toString().trim() : "";

        if (current.isEmpty() || newPass.isEmpty() || confirm.isEmpty()) {
            Toast.makeText(this, "Заполните все поля", Toast.LENGTH_SHORT).show();
            return;
        }
        if (newPass.length() < 6) {
            Toast.makeText(this, "Новый пароль должен быть не менее 6 символов", Toast.LENGTH_SHORT).show();
            return;
        }
        if (!newPass.equals(confirm)) {
            Toast.makeText(this, "Новый пароль и подтверждение не совпадают", Toast.LENGTH_SHORT).show();
            return;
        }
        if (token.isEmpty()) {
            Toast.makeText(this, "Необходимо войти в аккаунт", Toast.LENGTH_SHORT).show();
            return;
        }

        apiService.changePassword("Bearer " + token, new ChangePasswordRequest(current, newPass))
                .enqueue(new Callback<SimpleResponse>() {
                    @Override
                    public void onResponse(Call<SimpleResponse> call, Response<SimpleResponse> response) {
                        if (response.isSuccessful()) {
                            Toast.makeText(SettingsActivity.this, "Пароль успешно изменён", Toast.LENGTH_SHORT).show();
                            currentPasswordInput.setText("");
                            newPasswordInput.setText("");
                            confirmPasswordInput.setText("");
                        } else if (response.code() == 422) {
                            Toast.makeText(SettingsActivity.this, "Неверный текущий пароль", Toast.LENGTH_SHORT).show();
                        } else {
                            Toast.makeText(SettingsActivity.this, "Ошибка: " + response.code(), Toast.LENGTH_SHORT).show();
                        }
                    }
                    @Override
                    public void onFailure(Call<SimpleResponse> call, Throwable t) {
                        Toast.makeText(SettingsActivity.this, "Ошибка соединения", Toast.LENGTH_SHORT).show();
                    }
                });
    }

    private void bindValues() {
        String theme = prefs.getString("theme", "light");
        if ("dark".equals(theme)) {
            themeRadioGroup.check(R.id.themeDarkRadio);
        } else if ("system".equals(theme)) {
            themeRadioGroup.check(R.id.themeSystemRadio);
        } else {
            themeRadioGroup.check(R.id.themeLightRadio);
        }

        String language = prefs.getString("language", "ru");
        languageRadioGroup.check("be".equals(language) ? R.id.langBeRadio : R.id.langRuRadio);
    }

    private void bindActions() {
        themeRadioGroup.setOnCheckedChangeListener((group, checkedId) -> {
            String value = "light";
            if (checkedId == R.id.themeDarkRadio) value = "dark";
            if (checkedId == R.id.themeSystemRadio) value = "system";
            prefs.edit().putString("theme", value).apply();
            AppUi.applyTheme(this);
            recreate();
        });

        languageRadioGroup.setOnCheckedChangeListener((group, checkedId) -> {
            prefs.edit().putString("language", checkedId == R.id.langBeRadio ? "be" : "ru").apply();
            applyLanguage();
            Toast.makeText(this, AppUi.t(this, "Язык изменён", "Мова зменена"), Toast.LENGTH_SHORT).show();
        });
    }

    private void applyLanguage() {
        ((TextView) findViewById(R.id.settingsRoleTextView)).setText(AppUi.t(this, "Настройки", "Налады"));
        ((TextView) findViewById(R.id.settingsTitleTextView)).setText(AppUi.t(this, "Интерфейс", "Інтэрфейс"));
        ((TextView) findViewById(R.id.settingsSubtitleTextView)).setText(AppUi.t(this, "Тема и язык приложения", "Тэма і мова праграмы"));
        ((TextView) findViewById(R.id.themeTitleTextView)).setText(AppUi.t(this, "Тема", "Тэма"));
        ((android.widget.RadioButton) findViewById(R.id.themeLightRadio)).setText(AppUi.t(this, "Светлая", "Светлая"));
        ((android.widget.RadioButton) findViewById(R.id.themeDarkRadio)).setText(AppUi.t(this, "Тёмная", "Цёмная"));
        ((android.widget.RadioButton) findViewById(R.id.themeSystemRadio)).setText(AppUi.t(this, "Как в телефоне", "Як у тэлефоне"));
        ((TextView) findViewById(R.id.languageTitleTextView)).setText(AppUi.t(this, "Язык", "Мова"));
        navHomeTextView.setText(AppUi.t(this, "Главная", "Галоўная"));
        navSettingsTextView.setText(AppUi.t(this, "Настройки", "Налады"));
        if ("worker".equals(role)) {
            navTasksTextView.setText(AppUi.t(this, "Задачи", "Задачы"));
            navRouteTextView.setText(AppUi.t(this, "Маршрут", "Маршрут"));
            navMapTextView.setText(AppUi.t(this, "Карта", "Карта"));
        } else {
            navTasksTextView.setText(AppUi.t(this, "Заявка", "Заяўка"));
            navMapTextView.setText(AppUi.t(this, "Задачи", "Задачы"));
            navMapTextView.setVisibility(android.view.View.VISIBLE);
            navRouteTextView.setText(AppUi.t(this, "История", "Гісторыя"));
        }
    }

    private void setupBottomNav() {
        navSettingsTextView.setBackgroundResource(R.drawable.bg_bottom_nav_selected);
        navSettingsTextView.setTextColor(getColor(R.color.white));
        navHomeTextView.setBackgroundResource(R.drawable.bg_bottom_nav_plain);
        navHomeTextView.setTextColor(getColor(R.color.text_gray));
        navTasksTextView.setBackgroundResource(R.drawable.bg_bottom_nav_plain);
        navTasksTextView.setTextColor(getColor(R.color.text_gray));
        navRouteTextView.setBackgroundResource(R.drawable.bg_bottom_nav_plain);
        navRouteTextView.setTextColor(getColor(R.color.text_gray));
        navMapTextView.setBackgroundResource(R.drawable.bg_bottom_nav_plain);
        navMapTextView.setTextColor(getColor(R.color.text_gray));

        navHomeTextView.setOnClickListener(v -> openHome());
        if ("worker".equals(role)) {
            navTasksTextView.setOnClickListener(v -> {
                Intent intent = new Intent(this, TicketListActivity.class);
                intent.putExtra("mode", "worker");
                startActivity(intent);
            });
            navRouteTextView.setOnClickListener(v -> startActivity(new Intent(this, WorkerRouteActivity.class)));
            navMapTextView.setOnClickListener(v -> startActivity(new Intent(this, WorkerMapActivity.class)));
        } else {
            navTasksTextView.setOnClickListener(v -> startActivity(new Intent(this, CreateTicketActivity.class)));
            navMapTextView.setOnClickListener(v -> startActivity(new Intent(this, ResidentTaskActivity.class)));
            navRouteTextView.setOnClickListener(v -> {
                Intent intent = new Intent(this, TicketListActivity.class);
                intent.putExtra("mode", "my");
                startActivity(intent);
            });
        }
    }

    private void openHome() {
        Intent intent = new Intent(this, HomeActivity.class);
        intent.setFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP | Intent.FLAG_ACTIVITY_SINGLE_TOP);
        startActivity(intent);
    }
}
