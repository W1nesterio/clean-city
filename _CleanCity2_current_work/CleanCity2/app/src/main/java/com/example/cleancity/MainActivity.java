package com.example.cleancity;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.example.cleancity.api.ApiClient;
import com.example.cleancity.ResidentContainerActivity;
import com.example.cleancity.api.ApiService;
import com.example.cleancity.models.LoginRequest;
import com.example.cleancity.models.LoginResponse;
import com.example.cleancity.ui.AppUi;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class MainActivity extends AppCompatActivity {

    private TextInputEditText emailEditText;
    private TextInputEditText passwordEditText;
    private MaterialButton loginButton;
    private TextView registerTextView;
    private ProgressBar progressBar;
    private ApiService apiService;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        AppUi.applyTheme(this);
        super.onCreate(savedInstanceState);

        SharedPreferences preferences = getSharedPreferences("auth", MODE_PRIVATE);
        String token = preferences.getString("token", null);

        if (token != null && !token.isEmpty()) {
            openHomeScreen();
            return;
        }

        setContentView(R.layout.activity_main);

        emailEditText = findViewById(R.id.emailEditText);
        passwordEditText = findViewById(R.id.passwordEditText);
        loginButton = findViewById(R.id.loginButton);
        registerTextView = findViewById(R.id.registerTextView);
        progressBar = findViewById(R.id.progressBar);

        apiService = ApiClient.getClient(ApiClient.DEFAULT_BASE_URL).create(ApiService.class);

        loginButton.setOnClickListener(v -> login());
        registerTextView.setOnClickListener(v -> startActivity(new Intent(MainActivity.this, RegisterActivity.class)));

        AppUi.applyAll(this, "Вход", "Уваход");
    }

    private void login() {
        String email = emailEditText.getText() != null ? emailEditText.getText().toString().trim() : "";
        String password = passwordEditText.getText() != null ? passwordEditText.getText().toString().trim() : "";

        if (email.isEmpty() || password.isEmpty()) {
            Toast.makeText(this, "Введите email и пароль", Toast.LENGTH_SHORT).show();
            return;
        }

        setLoading(true);

        apiService.login(new LoginRequest(email, password)).enqueue(new Callback<LoginResponse>() {
            @Override
            public void onResponse(Call<LoginResponse> call, Response<LoginResponse> response) {
                setLoading(false);

                if (response.isSuccessful() && response.body() != null && response.body().getUser() != null) {
                    LoginResponse loginResponse = response.body();
                    saveAuthData(loginResponse);
                    Toast.makeText(MainActivity.this, "Вход выполнен", Toast.LENGTH_SHORT).show();
                    openHomeScreen();
                } else {
                    Toast.makeText(MainActivity.this, "Неверный email или пароль", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(Call<LoginResponse> call, Throwable t) {
                setLoading(false);
                Toast.makeText(MainActivity.this, "Ошибка подключения: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    private void setLoading(boolean loading) {
        progressBar.setVisibility(loading ? android.view.View.VISIBLE : android.view.View.GONE);
        loginButton.setEnabled(!loading);
        if (registerTextView != null) {
            registerTextView.setEnabled(!loading);
        }
    }

    private void saveAuthData(LoginResponse loginResponse) {
        SharedPreferences preferences = getSharedPreferences("auth", MODE_PRIVATE);
        preferences.edit()
                .putString("server_url", ApiClient.DEFAULT_BASE_URL)
                .putString("token", loginResponse.getToken())
                .putString("name", loginResponse.getUser().getName())
                .putString("email", loginResponse.getUser().getEmail())
                .putString("role", loginResponse.getUser().getRole())
                .putString("phone", loginResponse.getUser().getPhone())
                .putString("position", loginResponse.getUser().getPosition())
                .putString("avatar_url", loginResponse.getUser().getAvatarUrl())
                .apply();
    }

    private void openHomeScreen() {
        SharedPreferences prefs = getSharedPreferences("auth", MODE_PRIVATE);
        String role = prefs.getString("role", "resident");
        // Residents get smooth ViewPager2 navigation; workers keep the original home
        Class<?> target = "resident".equals(role) ? ResidentContainerActivity.class : HomeActivity.class;
        startActivity(new Intent(this, target));
        finish();
    }
}
