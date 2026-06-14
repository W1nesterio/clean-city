package com.example.cleancity;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.View;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.google.android.material.button.MaterialButton;
import com.google.android.material.card.MaterialCardView;

public class HomeActivity extends AppCompatActivity {

    private TextView headerTitleTextView;
    private TextView headerSubtitleTextView;
    private TextView infoTitleTextView;
    private TextView infoDescriptionTextView;
    private TextView secondaryTitleTextView;
    private TextView secondaryDescriptionTextView;
    private TextView footerHintTextView;
    private MaterialButton mainActionButton;
    private MaterialCardView secondaryCard;
    private MaterialButton logoutButton;

    private String role;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_home);

        headerTitleTextView = findViewById(R.id.headerTitleTextView);
        headerSubtitleTextView = findViewById(R.id.headerSubtitleTextView);
        infoTitleTextView = findViewById(R.id.infoTitleTextView);
        infoDescriptionTextView = findViewById(R.id.infoDescriptionTextView);
        secondaryTitleTextView = findViewById(R.id.secondaryTitleTextView);
        secondaryDescriptionTextView = findViewById(R.id.secondaryDescriptionTextView);
        footerHintTextView = findViewById(R.id.footerHintTextView);
        mainActionButton = findViewById(R.id.mainActionButton);
        secondaryCard = findViewById(R.id.secondaryCard);
        logoutButton = findViewById(R.id.logoutButton);

        SharedPreferences preferences = getSharedPreferences("auth", MODE_PRIVATE);
        role = preferences.getString("role", "resident");

        setupRoleUi();
        logoutButton.setOnClickListener(v -> logout());
    }

    private void setupRoleUi() {
        if ("worker".equals(role)) {
            headerTitleTextView.setText("Рабочая смена");
            headerSubtitleTextView.setText("Обрабатывайте назначенные заявки и закрывайте работы с фото");

            infoTitleTextView.setText("Назначенные заявки");
            infoDescriptionTextView.setText("Откройте список задач, примите заявку и отметьте выполнение.");
            mainActionButton.setText("Открыть задачи");

            secondaryCard.setVisibility(View.GONE);
            footerHintTextView.setText("После выполнения прикрепите фото результата");

            mainActionButton.setOnClickListener(v -> {
                Intent intent = new Intent(this, TicketListActivity.class);
                intent.putExtra("mode", "worker");
                startActivity(intent);
            });
            return;
        }

        if ("admin".equals(role)) {
            headerTitleTextView.setText("Администрирование");
            headerSubtitleTextView.setText("Панель управления доступна через веб-сайт");

            infoTitleTextView.setText("Админ-панель");
            infoDescriptionTextView.setText("Откройте сайт проекта в браузере, чтобы управлять заявками и исполнителями.");
            mainActionButton.setText("Понятно");

            secondaryCard.setVisibility(View.GONE);
            footerHintTextView.setText("В мобильном приложении доступны функции жителя и исполнителя");

            mainActionButton.setOnClickListener(v ->
                    Toast.makeText(this, "Админ-панель открывается в браузере", Toast.LENGTH_LONG).show()
            );
            return;
        }

        headerTitleTextView.setText("Сообщите о проблеме");
        headerSubtitleTextView.setText("Создайте заявку с фото и отметкой места");

        infoTitleTextView.setText("Новая заявка");
        infoDescriptionTextView.setText("Выберите категорию, добавьте фото и укажите место загрязнения.");
        mainActionButton.setText("Создать заявку");

        secondaryCard.setVisibility(View.VISIBLE);
        secondaryTitleTextView.setText("Мои заявки");
        secondaryDescriptionTextView.setText("Проверить статус и историю обращений");
        footerHintTextView.setText("Заявка появится в админ-панели сразу после отправки");

        mainActionButton.setOnClickListener(v -> startActivity(new Intent(this, CreateTicketActivity.class)));
        secondaryCard.setOnClickListener(v -> {
            Intent intent = new Intent(this, TicketListActivity.class);
            intent.putExtra("mode", "my");
            startActivity(intent);
        });
    }

    private void logout() {
        getSharedPreferences("auth", MODE_PRIVATE).edit().clear().apply();
        Intent intent = new Intent(this, MainActivity.class);
        intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
        startActivity(intent);
    }
}
