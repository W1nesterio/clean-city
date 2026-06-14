package com.example.cleancity;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.View;
import android.widget.ProgressBar;
import android.widget.RadioGroup;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.example.cleancity.api.ApiClient;
import com.example.cleancity.api.ApiService;
import com.example.cleancity.models.LoginResponse;
import com.example.cleancity.models.RegisterRequest;
import com.example.cleancity.models.WorkerRegisterRequest;
import com.example.cleancity.ui.AppUi;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

import java.util.Locale;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class RegisterActivity extends AppCompatActivity {

    private RadioGroup registrationTypeRadioGroup;
    private TextInputEditText nameEditText;
    private TextInputEditText emailEditText;
    private TextInputEditText passwordEditText;
    private TextInputEditText passwordConfirmEditText;
    private TextInputEditText workerCodeEditText;
    private View workerCodeInputLayout;
    private MaterialButton registerButton;
    private MaterialButton registerBackButton;
    private TextView backToLoginTextView;
    private ProgressBar progressBar;

    private ApiService apiService;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        AppUi.applyTheme(this);
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_register);

        registrationTypeRadioGroup = findViewById(R.id.registrationTypeRadioGroup);
        nameEditText = findViewById(R.id.nameEditText);
        emailEditText = findViewById(R.id.emailEditText);
        passwordEditText = findViewById(R.id.passwordEditText);
        passwordConfirmEditText = findViewById(R.id.passwordConfirmEditText);
        workerCodeEditText = findViewById(R.id.workerCodeEditText);
        workerCodeInputLayout = findViewById(R.id.workerCodeInputLayout);
        registerButton = findViewById(R.id.registerButton);
        registerBackButton = findViewById(R.id.registerBackButton);
        backToLoginTextView = findViewById(R.id.backToLoginTextView);
        progressBar = findViewById(R.id.progressBar);

        String serverUrl = getSharedPreferences("auth", MODE_PRIVATE).getString("server_url", ApiClient.DEFAULT_BASE_URL);
        apiService = ApiClient.getClient(serverUrl).create(ApiService.class);

        registrationTypeRadioGroup.setOnCheckedChangeListener((group, checkedId) -> updateRegistrationTypeUi());
        registerButton.setOnClickListener(v -> register());
        registerBackButton.setOnClickListener(v -> finish());
        backToLoginTextView.setOnClickListener(v -> finish());

        updateRegistrationTypeUi();
        AppUi.applyAll(this, "Регистрация", "Рэгістрацыя");
    }

    private void updateRegistrationTypeUi() {
        boolean workerRegistration = isWorkerRegistration();
        workerCodeInputLayout.setVisibility(workerRegistration ? View.VISIBLE : View.GONE);
        registerButton.setText(workerRegistration ? "Создать кабинет" : "Зарегистрироваться");
    }

    private boolean isWorkerRegistration() {
        return registrationTypeRadioGroup.getCheckedRadioButtonId() == R.id.workerRadioButton;
    }

    private void register() {
        String name = text(nameEditText);
        String email = text(emailEditText);
        String password = text(passwordEditText);
        String passwordConfirm = text(passwordConfirmEditText);
        String workerCode = text(workerCodeEditText).toUpperCase(Locale.ROOT);
        boolean workerRegistration = isWorkerRegistration();

        if (name.isEmpty()) {
            Toast.makeText(this, "Введите имя", Toast.LENGTH_SHORT).show();
            return;
        }

        if (email.isEmpty() || password.isEmpty() || passwordConfirm.isEmpty()) {
            Toast.makeText(this, "Заполните все поля", Toast.LENGTH_SHORT).show();
            return;
        }

        if (workerRegistration && workerCode.isEmpty()) {
            Toast.makeText(this, "Введите ключ доступа", Toast.LENGTH_SHORT).show();
            return;
        }

        if (password.length() < 6) {
            Toast.makeText(this, "Пароль должен быть не короче 6 символов", Toast.LENGTH_SHORT).show();
            return;
        }

        if (!password.equals(passwordConfirm)) {
            Toast.makeText(this, "Пароли не совпадают", Toast.LENGTH_SHORT).show();
            return;
        }

        setLoading(true);

        Call<LoginResponse> call = workerRegistration
                ? apiService.registerWorker(new WorkerRegisterRequest(name, email, password, workerCode))
                : apiService.register(new RegisterRequest(name, email, password));

        call.enqueue(new Callback<LoginResponse>() {
            @Override
            public void onResponse(Call<LoginResponse> call, Response<LoginResponse> response) {
                setLoading(false);

                if (response.isSuccessful() && response.body() != null && response.body().getUser() != null) {
                    LoginResponse registerResponse = response.body();
                    saveAuthData(registerResponse);
                    Toast.makeText(RegisterActivity.this, workerRegistration ? "Кабинет сотрудника создан" : "Аккаунт создан", Toast.LENGTH_SHORT).show();
                    openHomeScreen();
                } else if (response.code() == 422) {
                    Toast.makeText(RegisterActivity.this, workerRegistration ? "Проверьте email, пароль и ключ доступа" : "Такой email уже используется или данные заполнены неверно", Toast.LENGTH_LONG).show();
                } else {
                    Toast.makeText(RegisterActivity.this, "Регистрация не выполнена", Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onFailure(Call<LoginResponse> call, Throwable t) {
                setLoading(false);
                Toast.makeText(RegisterActivity.this, "Ошибка подключения: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    private String text(TextInputEditText editText) {
        return editText.getText() != null ? editText.getText().toString().trim() : "";
    }

    private void setLoading(boolean loading) {
        progressBar.setVisibility(loading ? View.VISIBLE : View.GONE);
        registerButton.setEnabled(!loading);
        backToLoginTextView.setEnabled(!loading);
        registrationTypeRadioGroup.setEnabled(!loading);
    }

    private void saveAuthData(LoginResponse registerResponse) {
        SharedPreferences preferences = getSharedPreferences("auth", MODE_PRIVATE);
        preferences.edit()
                .putString("server_url", ApiClient.DEFAULT_BASE_URL)
                .putString("token", registerResponse.getToken())
                .putString("name", registerResponse.getUser().getName())
                .putString("email", registerResponse.getUser().getEmail())
                .putString("role", registerResponse.getUser().getRole())
                .putString("phone", registerResponse.getUser().getPhone())
                .putString("position", registerResponse.getUser().getPosition())
                .putString("avatar_url", registerResponse.getUser().getAvatarUrl())
                .apply();
    }

    private void openHomeScreen() {
        Intent intent = new Intent(this, HomeActivity.class);
        intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
        startActivity(intent);
        finish();
    }
}
