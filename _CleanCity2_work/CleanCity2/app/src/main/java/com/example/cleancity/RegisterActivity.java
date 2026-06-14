package com.example.cleancity;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.View;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.example.cleancity.api.ApiClient;
import com.example.cleancity.api.ApiService;
import com.example.cleancity.models.LoginResponse;
import com.example.cleancity.models.RegisterRequest;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class RegisterActivity extends AppCompatActivity {

    private TextInputEditText nameEditText;
    private TextInputEditText emailEditText;
    private TextInputEditText passwordEditText;
    private TextInputEditText passwordConfirmEditText;
    private MaterialButton registerButton;
    private TextView backToLoginTextView;
    private ProgressBar progressBar;

    private ApiService apiService;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_register);

        nameEditText = findViewById(R.id.nameEditText);
        emailEditText = findViewById(R.id.emailEditText);
        passwordEditText = findViewById(R.id.passwordEditText);
        passwordConfirmEditText = findViewById(R.id.passwordConfirmEditText);
        registerButton = findViewById(R.id.registerButton);
        backToLoginTextView = findViewById(R.id.backToLoginTextView);
        progressBar = findViewById(R.id.progressBar);

        apiService = ApiClient.getClient(ApiClient.DEFAULT_BASE_URL).create(ApiService.class);

        registerButton.setOnClickListener(v -> register());
        backToLoginTextView.setOnClickListener(v -> finish());
    }

    private void register() {
        String name = nameEditText.getText() != null
                ? nameEditText.getText().toString().trim()
                : "";

        String email = emailEditText.getText() != null
                ? emailEditText.getText().toString().trim()
                : "";

        String password = passwordEditText.getText() != null
                ? passwordEditText.getText().toString().trim()
                : "";

        String passwordConfirm = passwordConfirmEditText.getText() != null
                ? passwordConfirmEditText.getText().toString().trim()
                : "";

        if (name.isEmpty()) {
            Toast.makeText(this, "Введите имя", Toast.LENGTH_SHORT).show();
            return;
        }

        if (email.isEmpty() || password.isEmpty() || passwordConfirm.isEmpty()) {
            Toast.makeText(this, "Заполните все поля", Toast.LENGTH_SHORT).show();
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

        RegisterRequest request = new RegisterRequest(name, email, password);

        apiService.register(request).enqueue(new Callback<LoginResponse>() {
            @Override
            public void onResponse(Call<LoginResponse> call, Response<LoginResponse> response) {
                setLoading(false);

                if (response.isSuccessful() && response.body() != null && response.body().getUser() != null) {
                    LoginResponse registerResponse = response.body();

                    saveAuthData(
                            registerResponse.getToken(),
                            registerResponse.getUser().getName(),
                            registerResponse.getUser().getEmail(),
                            registerResponse.getUser().getRole()
                    );

                    Toast.makeText(RegisterActivity.this, "Аккаунт создан", Toast.LENGTH_SHORT).show();
                    openHomeScreen();
                } else if (response.code() == 422) {
                    Toast.makeText(RegisterActivity.this, "Такой email уже используется или данные заполнены неверно", Toast.LENGTH_LONG).show();
                } else {
                    Toast.makeText(RegisterActivity.this, "Не удалось зарегистрироваться", Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onFailure(Call<LoginResponse> call, Throwable t) {
                setLoading(false);
                Toast.makeText(RegisterActivity.this, "Ошибка подключения: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    private void setLoading(boolean loading) {
        progressBar.setVisibility(loading ? View.VISIBLE : View.GONE);
        registerButton.setEnabled(!loading);
        backToLoginTextView.setEnabled(!loading);
    }

    private void saveAuthData(String token, String name, String email, String role) {
        SharedPreferences preferences = getSharedPreferences("auth", MODE_PRIVATE);
        preferences.edit()
                .putString("server_url", ApiClient.DEFAULT_BASE_URL)
                .putString("token", token)
                .putString("name", name)
                .putString("email", email)
                .putString("role", role)
                .apply();
    }

    private void openHomeScreen() {
        Intent intent = new Intent(this, HomeActivity.class);
        intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
        startActivity(intent);
        finish();
    }
}
