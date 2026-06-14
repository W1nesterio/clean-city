package com.example.cleancity;

import android.content.Intent;
import android.content.SharedPreferences;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.os.Bundle;
import android.view.View;
import android.widget.FrameLayout;
import android.widget.LinearLayout;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.example.cleancity.api.ApiClient;
import com.example.cleancity.api.ApiService;
import com.example.cleancity.models.PointsResponse;
import com.example.cleancity.models.Ticket;
import com.example.cleancity.models.TicketsResponse;
import com.example.cleancity.ui.CircleImageView;
import com.example.cleancity.ui.AppUi;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.card.MaterialCardView;

import java.net.HttpURLConnection;
import java.net.URL;
import java.util.List;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class HomeActivity extends AppCompatActivity {

    private TextView roleTextView;
    private TextView titleTextView;
    private TextView subtitleTextView;
    private TextView primaryTitleTextView;
    private TextView primaryDescriptionTextView;
    private TextView primaryActionTextView;
    private TextView secondaryTitleTextView;
    private TextView secondaryDescriptionTextView;
    private TextView secondaryActionTextView;
    private TextView tertiaryTitleTextView;
    private TextView tertiaryDescriptionTextView;
    private TextView tertiaryActionTextView;
    private TextView hintTextView;
    private TextView avatarInitialsTextView;
    private TextView homeSectionTitleTextView;
    private TextView homeActiveCountTextView;
    private TextView homeWorkCountTextView;
    private TextView homeDoneCountTextView;
    private TextView homeNextStepTextView;
    private TextView navHomeTextView;
    private TextView navTasksTextView;
    private TextView navRouteTextView;
    private TextView navMapTextView;
    private TextView navProfileTextView;
    private CircleImageView avatarImageView;
    private FrameLayout profileAvatarContainer;
    private LinearLayout workerDashboardContainer;
    private LinearLayout bottomNavContainer;
    private MaterialCardView primaryCard;
    private MaterialCardView secondaryCard;
    private MaterialCardView tertiaryCard;
    private MaterialCardView summaryCard;
    private MaterialCardView pointsCard;
    private MaterialButton logoutButton;
    private MaterialButton rewardButton;
    private MaterialButton newsButton;
    private TextView pointsCountTextView;

    private String role;
    private String token;
    private ApiService apiService;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        AppUi.applyTheme(this);
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_home);

        roleTextView = findViewById(R.id.homeRoleTextView);
        titleTextView = findViewById(R.id.homeTitleTextView);
        subtitleTextView = findViewById(R.id.homeSubtitleTextView);
        primaryTitleTextView = findViewById(R.id.primaryTitleTextView);
        primaryDescriptionTextView = findViewById(R.id.primaryDescriptionTextView);
        primaryActionTextView = findViewById(R.id.primaryActionTextView);
        secondaryTitleTextView = findViewById(R.id.secondaryTitleTextView);
        secondaryDescriptionTextView = findViewById(R.id.secondaryDescriptionTextView);
        secondaryActionTextView = findViewById(R.id.secondaryActionTextView);
        tertiaryTitleTextView = findViewById(R.id.tertiaryTitleTextView);
        tertiaryDescriptionTextView = findViewById(R.id.tertiaryDescriptionTextView);
        tertiaryActionTextView = findViewById(R.id.tertiaryActionTextView);
        profileAvatarContainer = findViewById(R.id.profileAvatarContainer);
        avatarInitialsTextView = findViewById(R.id.homeAvatarInitialsTextView);
        avatarImageView = findViewById(R.id.homeAvatarImageView);
        homeSectionTitleTextView = findViewById(R.id.homeSectionTitleTextView);
        workerDashboardContainer = findViewById(R.id.workerDashboardContainer);
        homeActiveCountTextView = findViewById(R.id.homeActiveCountTextView);
        homeWorkCountTextView = findViewById(R.id.homeWorkCountTextView);
        homeDoneCountTextView = findViewById(R.id.homeDoneCountTextView);
        homeNextStepTextView = findViewById(R.id.homeNextStepTextView);
        bottomNavContainer = findViewById(R.id.bottomNavContainer);
        navHomeTextView = findViewById(R.id.navHomeTextView);
        navTasksTextView = findViewById(R.id.navTasksTextView);
        navRouteTextView = findViewById(R.id.navRouteTextView);
        navMapTextView = findViewById(R.id.navMapTextView);
        navProfileTextView = findViewById(R.id.navProfileTextView);
        primaryCard = findViewById(R.id.primaryCard);
        secondaryCard = findViewById(R.id.secondaryCard);
        tertiaryCard = findViewById(R.id.tertiaryCard);
        summaryCard = findViewById(R.id.summaryCard);
        pointsCard = findViewById(R.id.pointsCard);
        logoutButton = findViewById(R.id.logoutButton);
        rewardButton = findViewById(R.id.rewardButton);
        newsButton = findViewById(R.id.newsButton);
        pointsCountTextView = findViewById(R.id.pointsCountTextView);

        SharedPreferences preferences = getSharedPreferences("auth", MODE_PRIVATE);
        role = preferences.getString("role", "resident");
        token = preferences.getString("token", "");
        String serverUrl = preferences.getString("server_url", ApiClient.DEFAULT_BASE_URL);
        apiService = ApiClient.getClient(serverUrl).create(ApiService.class);

        setupRoleUi();
        logoutButton.setOnClickListener(v -> logout());
        profileAvatarContainer.setContentDescription("Открыть профиль");
        profileAvatarContainer.setOnClickListener(v -> { AppUi.feedback(this, v); openProfile(); });
        AppUi.applyAll(this, "Главная", "Галоўная");
    }

    @Override
    protected void onResume() {
        super.onResume();
        if ("worker".equals(role)) {
            fillHeaderAvatar();
            loadWorkerSummary();
        } else if ("resident".equals(role)) {
            loadResidentSummary();
            loadResidentPoints();
        }
    }

    private void setupRoleUi() {
        SharedPreferences preferences = getSharedPreferences("auth", MODE_PRIVATE);

        workerDashboardContainer.setVisibility(View.GONE);
        bottomNavContainer.setVisibility(View.GONE);
        homeSectionTitleTextView.setVisibility(View.GONE);
        primaryCard.setVisibility(View.VISIBLE);
        secondaryCard.setVisibility(View.VISIBLE);
        tertiaryCard.setVisibility(View.VISIBLE);
        hintTextView.setVisibility(View.GONE);
        profileAvatarContainer.setVisibility(View.GONE);
        logoutButton.setVisibility(View.VISIBLE);

        if ("worker".equals(role)) {
            roleTextView.setText(AppUi.t(this, "Исполнитель", "Выканаўца"));
            titleTextView.setText(AppUi.t(this, "Рабочий день", "Працоўны дзень"));
            subtitleTextView.setText(AppUi.t(this, "Сводка по задачам", "Зводка па задачах"));

            primaryCard.setVisibility(View.GONE);
            secondaryCard.setVisibility(View.GONE);
            tertiaryCard.setVisibility(View.GONE);
            workerDashboardContainer.setVisibility(View.VISIBLE);
            bottomNavContainer.setVisibility(View.VISIBLE);
            profileAvatarContainer.setVisibility(View.VISIBLE);
            logoutButton.setVisibility(View.GONE);

            fillHeaderAvatar();
            setupBottomNav("home");
            loadWorkerSummary();
            return;
        }

        if ("admin".equals(role)) {
            roleTextView.setText("Администратор");
            titleTextView.setText("Панель управления");
            subtitleTextView.setText("Админка открывается в браузере");

            primaryTitleTextView.setText("Веб-панель");
            primaryDescriptionTextView.setText("Заявки, сотрудники и статистика доступны на сайте.");
            primaryActionTextView.setText("Понятно");
            primaryCard.setOnClickListener(v -> Toast.makeText(this, "Откройте админ-панель в браузере", Toast.LENGTH_LONG).show());

            secondaryCard.setVisibility(View.GONE);
            tertiaryCard.setVisibility(View.GONE);
            return;
        }

        roleTextView.setText(AppUi.t(this, "Житель", "Жыхар"));
        titleTextView.setText(AppUi.t(this, "Чистый город", "Чысты горад"));
        subtitleTextView.setText(AppUi.t(this, "Обращения и статусы", "Звароты і статусы"));

        primaryCard.setVisibility(View.GONE);
        secondaryCard.setVisibility(View.GONE);
        tertiaryCard.setVisibility(View.GONE);
        workerDashboardContainer.setVisibility(View.VISIBLE);
        bottomNavContainer.setVisibility(View.VISIBLE);
        summaryCard.setVisibility(View.GONE);
        pointsCard.setVisibility(View.VISIBLE);

        rewardButton.setOnClickListener(v -> {
            AppUi.feedback(this, v);
            startActivity(new Intent(this, CouponsActivity.class));
        });
        newsButton.setOnClickListener(v -> {
            AppUi.feedback(this, v);
            startActivity(new Intent(this, NewsActivity.class));
        });

        setupResidentBottomNav();
        loadResidentSummary();
    }

    private void loadResidentSummary() {
        if (apiService == null || token == null || token.trim().isEmpty()) {
            return;
        }

        apiService.getMyTickets("Bearer " + token).enqueue(new Callback<TicketsResponse>() {
            @Override
            public void onResponse(Call<TicketsResponse> call, Response<TicketsResponse> response) {
                if (!response.isSuccessful() || response.body() == null || response.body().getTickets() == null) {
                    homeNextStepTextView.setText(AppUi.t(HomeActivity.this, "Данные временно недоступны", "Дадзеныя часова недаступныя"));
                    return;
                }
                int active = 0;
                int work = 0;
                int done = 0;
                for (Ticket ticket : response.body().getTickets()) {
                    String status = ticket.getStatus();
                    if ("completed".equals(status)) done++;
                    else if ("in_progress".equals(status) || "accepted".equals(status) || "assigned".equals(status)) work++;
                    else if (!"rejected".equals(status) && !"duplicate".equals(status)) active++;
                }
                homeActiveCountTextView.setText(String.valueOf(active));
                homeWorkCountTextView.setText(String.valueOf(work));
                homeDoneCountTextView.setText(String.valueOf(done));
                homeNextStepTextView.setText(AppUi.t(HomeActivity.this, "Новая заявка, история и настройки находятся в нижнем меню.", "Новая заяўка, гісторыя і налады знаходзяцца ў ніжнім меню."));
            }

            @Override
            public void onFailure(Call<TicketsResponse> call, Throwable t) {
                homeNextStepTextView.setText(AppUi.t(HomeActivity.this, "Не удалось обновить сводку", "Не атрымалася абнавіць зводку"));
            }
        });
    }

    private void loadResidentPoints() {
        if (apiService == null || token == null || token.trim().isEmpty()) return;
        apiService.getMyPoints("Bearer " + token).enqueue(new Callback<PointsResponse>() {
            @Override
            public void onResponse(Call<PointsResponse> call, Response<PointsResponse> response) {
                if (response.isSuccessful() && response.body() != null && pointsCountTextView != null) {
                    pointsCountTextView.setText(String.valueOf(response.body().getPointsBalance()));
                }
            }
            @Override
            public void onFailure(Call<PointsResponse> call, Throwable t) {}
        });
    }

    private void loadWorkerSummary() {
        if (apiService == null || token == null || token.trim().isEmpty()) {
            return;
        }

        apiService.getWorkerTickets("Bearer " + token).enqueue(new Callback<TicketsResponse>() {
            @Override
            public void onResponse(Call<TicketsResponse> call, Response<TicketsResponse> response) {
                if (!response.isSuccessful() || response.body() == null || response.body().getTickets() == null) {
                    homeNextStepTextView.setText("Данные временно недоступны. Обновите экран позже.");
                    return;
                }

                List<Ticket> tickets = response.body().getTickets();
                int active = 0;
                int work = 0;
                int done = 0;
                int assigned = 0;
                int accepted = 0;

                for (Ticket ticket : tickets) {
                    String status = ticket.getStatus();
                    if ("completed".equals(status)) {
                        done++;
                    } else if (!"rejected".equals(status) && !"duplicate".equals(status)) {
                        active++;
                    }

                    if ("assigned".equals(status)) assigned++;
                    if ("accepted".equals(status)) accepted++;
                    if ("in_progress".equals(status) || "problem".equals(status) || "postponed".equals(status)) work++;
                }

                homeActiveCountTextView.setText(String.valueOf(active));
                homeWorkCountTextView.setText(String.valueOf(work));
                homeDoneCountTextView.setText(String.valueOf(done));

                if (work > 0) {
                    homeNextStepTextView.setText(AppUi.t(HomeActivity.this, "Есть задачи в работе", "Ёсць задачы ў працы"));
                } else if (accepted > 0) {
                    homeNextStepTextView.setText(AppUi.t(HomeActivity.this, "Есть принятые задачи", "Ёсць прынятыя задачы"));
                } else if (assigned > 0) {
                    homeNextStepTextView.setText(AppUi.t(HomeActivity.this, "Есть новые задачи", "Ёсць новыя задачы"));
                } else {
                    homeNextStepTextView.setText(AppUi.t(HomeActivity.this, "Активных задач нет", "Актыўных задач няма"));
                }
            }

            @Override
            public void onFailure(Call<TicketsResponse> call, Throwable t) {
                homeNextStepTextView.setText("Не удалось обновить сводку.");
            }
        });
    }

    private void setupBottomNav(String active) {
        navHomeTextView.setVisibility(View.VISIBLE);
        navTasksTextView.setVisibility(View.VISIBLE);
        navRouteTextView.setVisibility(View.VISIBLE);
        navMapTextView.setVisibility(View.VISIBLE);
        navProfileTextView.setVisibility(View.VISIBLE);

        navHomeTextView.setText(AppUi.t(this, "Главная", "Галоўная"));
        navTasksTextView.setText(AppUi.t(this, "Задачи", "Задачы"));
        navRouteTextView.setText(AppUi.t(this, "Маршрут", "Маршрут"));
        navMapTextView.setText(AppUi.t(this, "Карта", "Карта"));
        navProfileTextView.setText(AppUi.t(this, "Настройки", "Налады"));

        setNavSelected(navHomeTextView, "home".equals(active));
        setNavSelected(navTasksTextView, "tasks".equals(active));
        setNavSelected(navRouteTextView, "route".equals(active));
        setNavSelected(navMapTextView, "map".equals(active));
        setNavSelected(navProfileTextView, "settings".equals(active));

        navHomeTextView.setOnClickListener(v -> { AppUi.feedback(this, v); });
        navTasksTextView.setOnClickListener(v -> { AppUi.feedback(this, v); openTasks(); });
        navRouteTextView.setOnClickListener(v -> { AppUi.feedback(this, v); startActivity(new Intent(this, WorkerRouteActivity.class)); });
        navMapTextView.setOnClickListener(v -> { AppUi.feedback(this, v); startActivity(new Intent(this, WorkerMapActivity.class)); });
        navProfileTextView.setOnClickListener(v -> { AppUi.feedback(this, v); startActivity(new Intent(this, SettingsActivity.class)); });
    }

    private void setupResidentBottomNav() {
        navHomeTextView.setVisibility(View.VISIBLE);
        navTasksTextView.setVisibility(View.VISIBLE);
        navMapTextView.setVisibility(View.VISIBLE);
        navRouteTextView.setVisibility(View.VISIBLE);
        navProfileTextView.setVisibility(View.VISIBLE);

        navHomeTextView.setText(AppUi.t(this, "Главная", "Галоўная"));
        navTasksTextView.setText(AppUi.t(this, "Заявка", "Заяўка"));
        navMapTextView.setText(AppUi.t(this, "Задачи", "Задачы"));
        navRouteTextView.setText(AppUi.t(this, "История", "Гісторыя"));
        navProfileTextView.setText(AppUi.t(this, "Настройки", "Налады"));

        setNavSelected(navHomeTextView, true);
        setNavSelected(navTasksTextView, false);
        setNavSelected(navMapTextView, false);
        setNavSelected(navRouteTextView, false);
        setNavSelected(navProfileTextView, false);

        navHomeTextView.setOnClickListener(v -> { AppUi.feedback(this, v); });
        navTasksTextView.setOnClickListener(v -> { AppUi.feedback(this, v); startActivity(new Intent(this, CreateTicketActivity.class)); });
        navMapTextView.setOnClickListener(v -> { AppUi.feedback(this, v); startActivity(new Intent(this, ResidentTaskActivity.class)); });
        navRouteTextView.setOnClickListener(v -> {
            AppUi.feedback(this, v);
            Intent intent = new Intent(this, TicketListActivity.class);
            intent.putExtra("mode", "my");
            startActivity(intent);
        });
        navProfileTextView.setOnClickListener(v -> { AppUi.feedback(this, v); startActivity(new Intent(this, SettingsActivity.class)); });
    }

    private void setNavSelected(TextView item, boolean selected) {
        item.setBackgroundResource(selected ? R.drawable.bg_bottom_nav_selected : R.drawable.bg_bottom_nav_plain);
        item.setTextColor(selected ? getColor(R.color.white) : getColor(R.color.text_gray));
    }

    private void openTasks() {
        Intent intent = new Intent(this, TicketListActivity.class);
        intent.putExtra("mode", "worker");
        startActivity(intent);
    }

    private void openProfile() {
        startActivity(new Intent(this, WorkerProfileActivity.class));
    }

    private void fillHeaderAvatar() {
        SharedPreferences preferences = getSharedPreferences("auth", MODE_PRIVATE);
        String name = preferences.getString("name", "Сотрудник");
        String avatarUrl = preferences.getString("avatar_url", "");

        avatarInitialsTextView.setText(initials(name));
        if (avatarUrl == null || avatarUrl.trim().isEmpty()) {
            avatarImageView.setVisibility(View.GONE);
            avatarInitialsTextView.setVisibility(View.VISIBLE);
            return;
        }

        loadAvatarIntoHeader(avatarUrl);
    }

    private void loadAvatarIntoHeader(String avatarUrl) {
        new Thread(() -> {
            HttpURLConnection connection = null;
            try {
                URL url = new URL(avatarUrl);
                connection = (HttpURLConnection) url.openConnection();
                connection.setConnectTimeout(5000);
                connection.setReadTimeout(5000);
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

    private String firstName(String name) {
        if (name == null || name.trim().isEmpty()) {
            return "";
        }
        return name.trim().split("\\s+")[0];
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

    private void logout() {
        getSharedPreferences("auth", MODE_PRIVATE).edit().clear().apply();
        AppUi.resetAuthMode(this);
        Intent intent = new Intent(this, MainActivity.class);
        intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
        startActivity(intent);
    }
}
