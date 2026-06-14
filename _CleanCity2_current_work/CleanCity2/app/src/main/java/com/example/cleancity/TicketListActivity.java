package com.example.cleancity;

import android.Manifest;
import android.app.AlertDialog;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.res.ColorStateList;
import android.content.pm.PackageManager;
import android.database.Cursor;
import android.graphics.Color;
import android.net.Uri;
import android.os.Bundle;
import android.provider.MediaStore;
import android.provider.OpenableColumns;
import android.text.InputType;
import android.view.View;
import android.widget.EditText;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.ScrollView;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.content.ContextCompat;
import androidx.core.content.FileProvider;

import com.bumptech.glide.Glide;
import com.example.cleancity.api.ApiClient;
import com.example.cleancity.api.ApiService;
import com.example.cleancity.models.CreateTicketResponse;
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

public class TicketListActivity extends AppCompatActivity {

    private static final int PICK_AFTER_PHOTO_REQUEST = 3001;
    private static final int TAKE_AFTER_PHOTO_REQUEST = 3002;
    private static final int AFTER_CAMERA_PERMISSION_REQUEST = 3003;

    private TextView titleTextView;
    private TextView subtitleTextView;
    private LinearLayout ticketsContainer;
    private android.widget.HorizontalScrollView workerTabsScroll;
    private LinearLayout workerTabsContainer;
    private ProgressBar progressBar;
    private MaterialButton backButton;
    private MaterialButton openRoutePlannerButton;
    private MaterialButton openTaskMapButton;
    private LinearLayout bottomNavContainer;
    private TextView navHomeTextView;
    private TextView navTasksTextView;
    private TextView navRouteTextView;
    private TextView navMapTextView;
    private TextView navProfileTextView;
    private int focusTicketId = -1;
    private ApiService apiService;
    private String mode;
    private String token;
    private int ticketIdForAfterPhoto = -1;
    private String completionComment = "Работа выполнена";
    private File afterCameraFile;
    private Uri afterCameraUri;
    private final List<Ticket> loadedTickets = new ArrayList<>();
    private String currentFilter = "active";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        AppUi.applyTheme(this);
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_ticket_list);
        AppUi.applyAll(this, "Список заявок", "Спіс заявак");

        backButton = findViewById(R.id.listBackButton);
        titleTextView = findViewById(R.id.listTitleTextView);
        subtitleTextView = findViewById(R.id.listSubtitleTextView);
        ticketsContainer = findViewById(R.id.ticketsContainer);
        workerTabsScroll = findViewById(R.id.workerTabsScroll);
        workerTabsContainer = findViewById(R.id.workerTabsContainer);
        progressBar = findViewById(R.id.listProgressBar);
        openRoutePlannerButton = findViewById(R.id.openRoutePlannerButton);
        openTaskMapButton = findViewById(R.id.openTaskMapButton);
        bottomNavContainer = findViewById(R.id.bottomNavContainer);
        navHomeTextView = findViewById(R.id.navHomeTextView);
        navTasksTextView = findViewById(R.id.navTasksTextView);
        navRouteTextView = findViewById(R.id.navRouteTextView);
        navMapTextView = findViewById(R.id.navMapTextView);
        navProfileTextView = findViewById(R.id.navProfileTextView);
        backButton.setOnClickListener(v -> finish());

        SharedPreferences preferences = getSharedPreferences("auth", MODE_PRIVATE);
        String serverUrl = preferences.getString("server_url", ApiClient.DEFAULT_BASE_URL);
        apiService = ApiClient.getClient(serverUrl).create(ApiService.class);
        mode = getIntent().getStringExtra("mode");
        focusTicketId = getIntent().getIntExtra("focus_ticket_id", -1);
        if (mode == null) {
            mode = "my";
        }

        token = preferences.getString("token", "");

        if ("worker".equals(mode)) {
            if (focusTicketId > 0) {
                titleTextView.setText("Заявка №" + focusTicketId);
                subtitleTextView.setText("Открыта с карты");
                workerTabsScroll.setVisibility(View.GONE);
                workerTabsContainer.setVisibility(View.GONE);
            } else {
                titleTextView.setText("Задачи");
                subtitleTextView.setText("Новые, в работе, завершённые");
                workerTabsScroll.setVisibility(View.VISIBLE);
                workerTabsContainer.setVisibility(View.VISIBLE);
                setupWorkerTabs();
            }
            openRoutePlannerButton.setVisibility(View.GONE);
            openTaskMapButton.setVisibility(View.GONE);
            bottomNavContainer.setVisibility(View.GONE);
            bottomNavContainer.setVisibility(View.VISIBLE);
            setupBottomNav("tasks");
        } else {
            titleTextView.setText(AppUi.t(this, "Мои заявки", "Мае заяўкі"));
            subtitleTextView.setText(AppUi.t(this, "Статусы обращений", "Статусы зваротаў"));
            workerTabsScroll.setVisibility(View.GONE);
            workerTabsContainer.setVisibility(View.GONE);
            openRoutePlannerButton.setVisibility(View.GONE);
            openTaskMapButton.setVisibility(View.GONE);
            bottomNavContainer.setVisibility(View.VISIBLE);
            setupResidentBottomNav("history");
        }

        loadTickets();
    }

    private void setupWorkerTabs() {
        workerTabsContainer.removeAllViews();
        addFilterButton("Активные", "active");
        addFilterButton("Новые", "new");
        addFilterButton("Принятые", "accepted");
        addFilterButton("В работе", "work");
        addFilterButton("Завершённые", "done");
    }

    private void addFilterButton(String label, String filter) {
        MaterialButton button = new MaterialButton(this);
        button.setText(label);
        button.setTextSize(13);
        button.setAllCaps(false);
        button.setCornerRadius(dp(16));
        button.setMinHeight(dp(42));
        button.setMinimumHeight(dp(42));
        button.setPadding(dp(12), 0, dp(12), 0);
        updateFilterButtonStyle(button, filter.equals(currentFilter));
        button.setOnClickListener(v -> {
            currentFilter = filter;
            setupWorkerTabs();
            showTickets(loadedTickets);
        });

        LinearLayout.LayoutParams params = new LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.WRAP_CONTENT,
                dp(42)
        );
        params.setMargins(0, 0, dp(8), 0);
        button.setLayoutParams(params);
        workerTabsContainer.addView(button);
    }

    private void updateFilterButtonStyle(MaterialButton button, boolean selected) {
        int background = Color.parseColor(selected ? "#146C43" : "#E7EFEA");
        int text = Color.parseColor(selected ? "#FFFFFF" : "#1F2933");
        button.setBackgroundTintList(ColorStateList.valueOf(background));
        button.setTextColor(text);
        button.setStrokeWidth(0);
    }

    private void loadTickets() {
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
                    loadedTickets.clear();
                    if (response.body().getTickets() != null) {
                        loadedTickets.addAll(response.body().getTickets());
                    }
                    showTickets(loadedTickets);
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

        List<Ticket> filteredTickets = filterTickets(tickets);

        if (filteredTickets.isEmpty()) {
            MaterialCardView emptyCard = new MaterialCardView(this);
            emptyCard.setRadius(dp(20));
            emptyCard.setCardElevation(dp(2));
            emptyCard.setCardBackgroundColor(Color.WHITE);
            emptyCard.setContentPadding(dp(18), dp(18), dp(18), dp(18));

            TextView emptyText = new TextView(this);
            emptyText.setText("Задач нет");
            emptyText.setTextSize(17);
            emptyText.setTextColor(Color.parseColor("#6B7280"));
            emptyCard.addView(emptyText);

            ticketsContainer.addView(emptyCard);
            AppUi.apply(this);
            return;
        }

        for (Ticket ticket : filteredTickets) {
            ticketsContainer.addView(createTicketCard(ticket));
        }
        AppUi.apply(this);
    }

    private List<Ticket> filterTickets(List<Ticket> tickets) {
        List<Ticket> result = new ArrayList<>();
        if (tickets == null) {
            return result;
        }

        if (!"worker".equals(mode)) {
            result.addAll(tickets);
            return result;
        }

        if (focusTicketId > 0) {
            for (Ticket ticket : tickets) {
                if (ticket.getId() == focusTicketId) {
                    result.add(ticket);
                    return result;
                }
            }
            return result;
        }

        for (Ticket ticket : tickets) {
            String status = ticket.getStatus();
            if ("new".equals(currentFilter) && "assigned".equals(status)) {
                result.add(ticket);
            } else if ("accepted".equals(currentFilter) && "accepted".equals(status)) {
                result.add(ticket);
            } else if ("work".equals(currentFilter) && ("in_progress".equals(status) || "problem".equals(status) || "postponed".equals(status))) {
                result.add(ticket);
            } else if ("done".equals(currentFilter) && "completed".equals(status)) {
                result.add(ticket);
            } else if ("active".equals(currentFilter) && !"completed".equals(status)) {
                result.add(ticket);
            }
        }
        return result;
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
        card.setCardElevation(dp(4));
        card.setCardBackgroundColor(Color.WHITE);
        card.setContentPadding(dp(18), dp(18), dp(18), dp(18));

        LinearLayout content = new LinearLayout(this);
        content.setOrientation(LinearLayout.VERTICAL);

        LinearLayout head = new LinearLayout(this);
        head.setOrientation(LinearLayout.HORIZONTAL);
        head.setGravity(android.view.Gravity.CENTER_VERTICAL);

        TextView title = new TextView(this);
        String categoryName = ticket.getCategory() != null ? ticket.getCategory().getName() : "Категория";
        title.setText("№" + ticket.getId() + " · " + categoryName);
        title.setTextColor(Color.parseColor("#1F2933"));
        title.setTextSize(18);
        title.setTypeface(null, android.graphics.Typeface.BOLD);
        LinearLayout.LayoutParams titleParams = new LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1);
        title.setLayoutParams(titleParams);
        head.addView(title);

        TextView statusBadge = new TextView(this);
        statusBadge.setText(ticket.getStatusLabel());
        statusBadge.setTextColor(Color.parseColor(statusColor(ticket.getStatus())));
        statusBadge.setTextSize(12);
        statusBadge.setTypeface(null, android.graphics.Typeface.BOLD);
        statusBadge.setPadding(dp(10), dp(5), dp(10), dp(5));
        statusBadge.setBackgroundColor(Color.parseColor(statusBackground(ticket.getStatus())));
        head.addView(statusBadge);
        content.addView(head);

        TextView location = new TextView(this);
        location.setText(locationText(ticket));
        location.setTextColor(Color.parseColor("#4B5563"));
        location.setTextSize(14);
        location.setPadding(0, dp(10), 0, 0);
        content.addView(location);

        TextView description = new TextView(this);
        String desc = ticket.getDescription() != null && !ticket.getDescription().trim().isEmpty()
                ? ticket.getDescription().trim()
                : "Без комментария";
        description.setText(desc);
        description.setTextColor(Color.parseColor("#6B7280"));
        description.setTextSize(14);
        description.setPadding(0, dp(8), 0, 0);
        content.addView(description);

        TextView date = new TextView(this);
        date.setText("Создана: " + safeText(ticket.getCreatedAt(), "—"));
        date.setTextColor(Color.parseColor("#89948D"));
        date.setTextSize(12);
        date.setPadding(0, dp(8), 0, 0);
        content.addView(date);

        if ("worker".equals(mode)) {
            addWorkerActions(content, ticket);
        } else {
            // Resident: tap card to open detail
            card.setClickable(true);
            card.setFocusable(true);
            card.setOnClickListener(v -> showResidentTicketDetail(ticket));
        }

        card.addView(content);
        return card;
    }

    private void addWorkerActions(LinearLayout content, Ticket ticket) {
        String status = ticket.getStatus();

        LinearLayout actions = new LinearLayout(this);
        actions.setOrientation(LinearLayout.VERTICAL);
        actions.setPadding(0, dp(10), 0, 0);

        MaterialButton routeButton = makeFullButton("Открыть маршрут", "#374151");
        routeButton.setOnClickListener(v -> openNavigation(ticket));
        actions.addView(routeButton);

        if ("assigned".equals(status)) {
            MaterialButton acceptButton = makeFullButton("Принять задачу", "#146C43");
            acceptButton.setOnClickListener(v -> changeStatus(ticket.getId(), "accepted", "Заявка принята исполнителем"));
            actions.addView(acceptButton);

            MaterialButton rejectButton = makeFullButton("Отклонить", "#991B1B");
            rejectButton.setOnClickListener(v -> askRejectReason(ticket.getId()));
            actions.addView(rejectButton);
        } else if ("accepted".equals(status)) {
            MaterialButton startButton = makeFullButton("Начать работу", "#1D4ED8");
            startButton.setOnClickListener(v -> changeStatus(ticket.getId(), "in_progress", "Исполнитель начал выполнение"));
            actions.addView(startButton);

            MaterialButton rejectButton = makeFullButton("Отклонить", "#991B1B");
            rejectButton.setOnClickListener(v -> askRejectReason(ticket.getId()));
            actions.addView(rejectButton);
        } else if ("in_progress".equals(status) || "problem".equals(status) || "postponed".equals(status)) {
            MaterialButton completeButton = makeFullButton("Завершить с фото", "#166534");
            completeButton.setOnClickListener(v -> askCompletionComment(ticket.getId()));
            actions.addView(completeButton);

            MaterialButton rejectButton = makeFullButton("Отклонить", "#991B1B");
            rejectButton.setOnClickListener(v -> askRejectReason(ticket.getId()));
            actions.addView(rejectButton);
        } else if ("completed".equals(status)) {
            TextView completedText = new TextView(this);
            completedText.setText("Закрыта");
            completedText.setTextColor(Color.parseColor("#166534"));
            completedText.setTextSize(14);
            completedText.setTypeface(null, android.graphics.Typeface.BOLD);
            completedText.setPadding(0, dp(8), 0, 0);
            actions.addView(completedText);
        }

        content.addView(actions);
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
        navTasksTextView.setOnClickListener(v -> { AppUi.feedback(this, v); });
        navRouteTextView.setOnClickListener(v -> { AppUi.feedback(this, v); startActivity(new Intent(this, WorkerRouteActivity.class)); });
        navMapTextView.setOnClickListener(v -> { AppUi.feedback(this, v); startActivity(new Intent(this, WorkerMapActivity.class)); });
        navProfileTextView.setOnClickListener(v -> { AppUi.feedback(this, v); startActivity(new Intent(this, SettingsActivity.class)); });
    }

    private void setupResidentBottomNav(String active) {
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
        setNavSelected(navHomeTextView, "home".equals(active));
        setNavSelected(navTasksTextView, "create".equals(active));
        setNavSelected(navMapTextView, "tasks".equals(active));
        setNavSelected(navRouteTextView, "history".equals(active));
        setNavSelected(navProfileTextView, "settings".equals(active));
        navHomeTextView.setOnClickListener(v -> openHome());
        navTasksTextView.setOnClickListener(v -> { AppUi.feedback(this, v); startActivity(new Intent(this, CreateTicketActivity.class)); });
        navMapTextView.setOnClickListener(v -> { AppUi.feedback(this, v); startActivity(new Intent(this, ResidentTaskActivity.class)); });
        navRouteTextView.setOnClickListener(v -> { AppUi.feedback(this, v); });
        navProfileTextView.setOnClickListener(v -> { AppUi.feedback(this, v); startActivity(new Intent(this, SettingsActivity.class)); });
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

    private String workerStepText(String status) {
        if ("assigned".equals(status)) {
            return "Новая задача: примите её, чтобы взять в работу.";
        }
        if ("accepted".equals(status)) {
            return "Задача закреплена за вами. Следующий шаг — начать работу.";
        }
        if ("in_progress".equals(status)) {
            return "Работа выполняется. Для закрытия нужно фото результата.";
        }
        if ("completed".equals(status)) {
            return "Выполнение завершено.";
        }
        return "Задача находится в обработке.";
    }

    private String locationText(Ticket ticket) {
        String address = ticket.getAddressText();
        if (address != null && !address.trim().isEmpty()) {
            return address.trim();
        }
        return "Место не указано";
    }

    /** Format ISO-8601 date to "dd.MM.yyyy HH:mm" */
    private String formatDate(String iso) {
        if (iso == null || iso.isEmpty()) return "—";
        try {
            String clean = iso.replaceAll("\\.\\d+Z?$", "").replace("Z", "");
            java.text.SimpleDateFormat in = new java.text.SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", java.util.Locale.getDefault());
            java.util.Date d = in.parse(clean);
            return new java.text.SimpleDateFormat("dd.MM.yyyy HH:mm", java.util.Locale.getDefault()).format(d);
        } catch (Exception e) {
            try { return iso.substring(8,10) + "." + iso.substring(5,7) + "." + iso.substring(0,4); }
            catch (Exception ignored) { return iso; }
        }
    }

    // ──────────────────────────────────────────────────────────────
    //  Worker: reject ticket dialog
    // ──────────────────────────────────────────────────────────────
    private void askRejectReason(int ticketId) {
        LinearLayout layout = new LinearLayout(this);
        layout.setOrientation(LinearLayout.VERTICAL);
        layout.setPadding(dp(22), dp(20), dp(22), dp(14));

        TextView title = new TextView(this);
        title.setText("Отклонить заявку");
        title.setTextColor(Color.parseColor("#991B1B"));
        title.setTextSize(20);
        title.setTypeface(null, android.graphics.Typeface.BOLD);
        layout.addView(title);

        TextView subtitle = new TextView(this);
        subtitle.setText("Укажите причину отклонения. Это обязательно.");
        subtitle.setTextColor(Color.parseColor("#6B7280"));
        subtitle.setTextSize(13);
        subtitle.setPadding(0, dp(6), 0, dp(12));
        layout.addView(subtitle);

        EditText input = new EditText(this);
        input.setHint("Причина отклонения");
        input.setInputType(InputType.TYPE_CLASS_TEXT | InputType.TYPE_TEXT_FLAG_MULTI_LINE);
        input.setMinLines(2);
        input.setMaxLines(4);
        input.setPadding(dp(12), dp(10), dp(12), dp(10));
        input.setBackgroundResource(R.drawable.bg_completion_option);
        LinearLayout.LayoutParams inputParams = new LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT, LinearLayout.LayoutParams.WRAP_CONTENT);
        inputParams.setMargins(0, 0, 0, dp(14));
        input.setLayoutParams(inputParams);
        layout.addView(input);

        AlertDialog dialog = new AlertDialog.Builder(this).create();

        LinearLayout buttons = new LinearLayout(this);
        buttons.setOrientation(LinearLayout.HORIZONTAL);

        MaterialButton confirmBtn = makeDialogButton("Отклонить", "#991B1B");
        confirmBtn.setOnClickListener(v -> {
            String reason = input.getText() != null ? input.getText().toString().trim() : "";
            if (reason.isEmpty()) {
                input.setError("Укажите причину");
                return;
            }
            dialog.dismiss();
            changeStatus(ticketId, "rejected", reason);
        });
        LinearLayout.LayoutParams p1 = new LinearLayout.LayoutParams(0, dp(52), 1);
        p1.setMargins(0, 0, dp(6), 0);
        buttons.addView(confirmBtn, p1);

        MaterialButton cancelBtn = makeDialogButton("Отмена", "#374151");
        cancelBtn.setOnClickListener(v -> dialog.dismiss());
        LinearLayout.LayoutParams p2 = new LinearLayout.LayoutParams(0, dp(52), 1);
        p2.setMargins(dp(6), 0, 0, 0);
        buttons.addView(cancelBtn, p2);

        layout.addView(buttons);
        dialog.setView(layout);
        dialog.show();
    }

    // ──────────────────────────────────────────────────────────────
    //  Resident: ticket detail dialog (photo + address + status)
    // ──────────────────────────────────────────────────────────────
    private void showResidentTicketDetail(Ticket ticket) {
        ScrollView scroll = new ScrollView(this);
        LinearLayout layout = new LinearLayout(this);
        layout.setOrientation(LinearLayout.VERTICAL);
        layout.setPadding(dp(22), dp(22), dp(22), dp(22));
        scroll.addView(layout);

        // Title
        TextView tvTitle = new TextView(this);
        String catName = ticket.getCategory() != null ? ticket.getCategory().getName() : "Заявка";
        tvTitle.setText("№" + ticket.getId() + " · " + catName);
        tvTitle.setTextColor(Color.parseColor("#1F2933"));
        tvTitle.setTextSize(20);
        tvTitle.setTypeface(null, android.graphics.Typeface.BOLD);
        layout.addView(tvTitle);

        // Status badge
        TextView tvStatus = new TextView(this);
        tvStatus.setText(ticket.getStatusLabel());
        tvStatus.setTextColor(Color.parseColor(statusColor(ticket.getStatus())));
        tvStatus.setTextSize(13);
        tvStatus.setTypeface(null, android.graphics.Typeface.BOLD);
        tvStatus.setPadding(dp(10), dp(5), dp(10), dp(5));
        tvStatus.setBackgroundColor(Color.parseColor(statusBackground(ticket.getStatus())));
        LinearLayout.LayoutParams statusParams = new LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.WRAP_CONTENT, LinearLayout.LayoutParams.WRAP_CONTENT);
        statusParams.setMargins(0, dp(10), 0, dp(10));
        tvStatus.setLayoutParams(statusParams);
        layout.addView(tvStatus);

        // Photo (before)
        boolean hasPhoto = ticket.getPhotos() != null && !ticket.getPhotos().isEmpty();
        if (hasPhoto) {
            String photoPath = null;
            for (com.example.cleancity.models.TicketPhoto p : ticket.getPhotos()) {
                if ("before".equals(p.getType()) || p.getType() == null) {
                    photoPath = p.getPath();
                    break;
                }
            }
            if (photoPath != null) {
                android.widget.ImageView photoView = new android.widget.ImageView(this);
                LinearLayout.LayoutParams photoParams = new LinearLayout.LayoutParams(
                        LinearLayout.LayoutParams.MATCH_PARENT, dp(200));
                photoParams.setMargins(0, 0, 0, dp(12));
                photoView.setLayoutParams(photoParams);
                photoView.setScaleType(android.widget.ImageView.ScaleType.CENTER_CROP);
                photoView.setBackgroundColor(Color.parseColor("#F3F6F1"));

                SharedPreferences prefs = getSharedPreferences("auth", MODE_PRIVATE);
                String serverUrl = prefs.getString("server_url", ApiClient.DEFAULT_BASE_URL);
                String storageBase = serverUrl.replace("/api/", "").replace("/api", "");
                String fullUrl = storageBase + "/storage/" + photoPath;

                com.bumptech.glide.Glide.with(this)
                        .load(fullUrl)
                        .placeholder(android.R.color.darker_gray)
                        .into(photoView);

                final String finalUrl = fullUrl;
                photoView.setOnClickListener(v -> {
                    android.content.Intent i = new android.content.Intent(this, FullscreenPhotoActivity.class);
                    i.putExtra("photo_url", finalUrl);
                    startActivity(i);
                });
                layout.addView(photoView);
            }
        }

        // Address — no raw coordinates
        addDetailRow(layout, "Адрес", locationText(ticket));

        // Description
        String desc = ticket.getDescription() != null && !ticket.getDescription().trim().isEmpty()
                ? ticket.getDescription().trim() : "Без комментария";
        addDetailRow(layout, "Описание", desc);

        // Formatted dates
        addDetailRow(layout, "Создана", formatDate(ticket.getCreatedAt()));
        if (ticket.getClosedAt() != null && !ticket.getClosedAt().isEmpty()) {
            addDetailRow(layout, "Закрыта", formatDate(ticket.getClosedAt()));
        }

        AlertDialog dialog = new AlertDialog.Builder(this)
                .setView(scroll)
                .setPositiveButton("Закрыть", null)
                .create();
        dialog.show();
    }

    private void addDetailRow(LinearLayout parent, String label, String value) {
        LinearLayout row = new LinearLayout(this);
        row.setOrientation(LinearLayout.VERTICAL);
        LinearLayout.LayoutParams rowParams = new LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT, LinearLayout.LayoutParams.WRAP_CONTENT);
        rowParams.setMargins(0, dp(4), 0, dp(12));
        row.setLayoutParams(rowParams);

        // Label — small caps
        TextView tvLabel = new TextView(this);
        tvLabel.setText(label);
        tvLabel.setTextColor(Color.parseColor("#6B7280"));
        tvLabel.setTextSize(11);
        tvLabel.setTypeface(null, android.graphics.Typeface.BOLD);
        tvLabel.setAllCaps(true);
        tvLabel.setLetterSpacing(0.08f);
        row.addView(tvLabel);

        // Value
        TextView tvValue = new TextView(this);
        tvValue.setText(value);
        tvValue.setTextColor(Color.parseColor("#111827"));
        tvValue.setTextSize(15);
        tvValue.setPadding(0, dp(4), 0, 0);
        row.addView(tvValue);

        // Thin separator
        android.view.View sep = new android.view.View(this);
        LinearLayout.LayoutParams sepParams = new LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT, 1);
        sepParams.setMargins(0, dp(10), 0, 0);
        sep.setLayoutParams(sepParams);
        sep.setBackgroundColor(Color.parseColor("#F0F4EE"));
        row.addView(sep);

        parent.addView(row);
    }

    private MaterialButton makeFullButton(String text, String color) {
        MaterialButton button = new MaterialButton(this);
        button.setText(text);
        button.setTextSize(14);
        button.setSingleLine(false);
        button.setMaxLines(2);
        button.setAllCaps(false);
        button.setCornerRadius(dp(14));
        button.setBackgroundTintList(ColorStateList.valueOf(Color.parseColor(color)));
        LinearLayout.LayoutParams params = new LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, dp(50));
        params.setMargins(0, dp(8), 0, 0);
        button.setLayoutParams(params);
        return button;
    }

    private String statusColor(String status) {
        if (status == null) return "#374151";
        switch (status) {
            case "created": return "#374151";    // grey (ТЗ: серый = новая)
            case "moderation": return "#374151";
            case "assigned": return "#92400E";
            case "accepted": return "#5B21B6";
            case "in_progress": return "#1D4ED8"; // blue = в работе (ТЗ)
            case "completed": return "#166534";   // green = выполнена (ТЗ)
            case "rejected": return "#991B1B";    // red = отклонена (ТЗ)
            default: return "#374151";
        }
    }

    private String statusBackground(String status) {
        if (status == null) return "#F3F4F6";
        switch (status) {
            case "created": return "#F3F4F6";    // grey
            case "moderation": return "#F3F4F6";
            case "assigned": return "#FEF3C7";
            case "accepted": return "#EDE9FE";
            case "in_progress": return "#DBEAFE";
            case "completed": return "#DCFCE7";
            case "rejected": return "#FEE2E2";
            default: return "#F3F4F6";
        }
    }

    private void changeStatus(int ticketId, String status, String comment) {
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
                    Toast.makeText(TicketListActivity.this, "Готово", Toast.LENGTH_SHORT).show();
                    loadTickets();
                } else {
                    Toast.makeText(TicketListActivity.this, "Не удалось обновить задачу", Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onFailure(Call<CreateTicketResponse> call, Throwable t) {
                progressBar.setVisibility(View.GONE);
                Toast.makeText(TicketListActivity.this, "Ошибка подключения: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    private void askCompletionComment(int ticketId) {
        ticketIdForAfterPhoto = ticketId;

        LinearLayout layout = new LinearLayout(this);
        layout.setOrientation(LinearLayout.VERTICAL);
        layout.setPadding(dp(22), dp(20), dp(22), dp(14));

        TextView title = new TextView(this);
        title.setText("Завершить работу");
        title.setTextColor(Color.parseColor("#1F2933"));
        title.setTextSize(21);
        title.setTypeface(null, android.graphics.Typeface.BOLD);
        layout.addView(title);

        TextView subtitle = new TextView(this);
        subtitle.setText("Добавьте фото результата. Комментарий можно оставить пустым.");
        subtitle.setTextColor(Color.parseColor("#6B7280"));
        subtitle.setTextSize(14);
        subtitle.setPadding(0, dp(6), 0, dp(12));
        layout.addView(subtitle);

        EditText input = new EditText(this);
        input.setHint("Комментарий");
        input.setInputType(InputType.TYPE_CLASS_TEXT | InputType.TYPE_TEXT_FLAG_MULTI_LINE);
        input.setMinLines(2);
        input.setMaxLines(4);
        input.setBackgroundColor(Color.TRANSPARENT);
        input.setPadding(dp(12), dp(10), dp(12), dp(10));
        LinearLayout.LayoutParams inputParams = new LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
        );
        inputParams.setMargins(0, 0, 0, dp(12));
        input.setLayoutParams(inputParams);
        input.setBackgroundResource(R.drawable.bg_completion_option);
        layout.addView(input);

        TextView photoTitle = new TextView(this);
        photoTitle.setText("Фото результата");
        photoTitle.setTextColor(Color.parseColor("#1F2933"));
        photoTitle.setTextSize(15);
        photoTitle.setTypeface(null, android.graphics.Typeface.BOLD);
        layout.addView(photoTitle);

        LinearLayout actions = new LinearLayout(this);
        actions.setOrientation(LinearLayout.HORIZONTAL);
        actions.setPadding(0, dp(10), 0, 0);

        AlertDialog dialog = new AlertDialog.Builder(this).create();

        MaterialButton cameraButton = makeDialogButton("Сделать фото", "#146C43");
        cameraButton.setOnClickListener(v -> {
            String text = input.getText() != null ? input.getText().toString().trim() : "";
            completionComment = text.isEmpty() ? "Работа выполнена" : text;
            dialog.dismiss();
            openAfterPhotoCamera(ticketId);
        });
        LinearLayout.LayoutParams cameraParams = new LinearLayout.LayoutParams(0, dp(52), 1);
        cameraParams.setMargins(0, 0, dp(8), 0);
        actions.addView(cameraButton, cameraParams);

        MaterialButton galleryButton = makeDialogButton("Из галереи", "#111827");
        galleryButton.setOnClickListener(v -> {
            String text = input.getText() != null ? input.getText().toString().trim() : "";
            completionComment = text.isEmpty() ? "Работа выполнена" : text;
            dialog.dismiss();
            openAfterPhotoGallery(ticketId);
        });
        LinearLayout.LayoutParams galleryParams = new LinearLayout.LayoutParams(0, dp(52), 1);
        galleryParams.setMargins(dp(8), 0, 0, 0);
        actions.addView(galleryButton, galleryParams);

        layout.addView(actions);

        TextView cancel = new TextView(this);
        cancel.setText("Отмена");
        cancel.setTextColor(Color.parseColor("#6B7280"));
        cancel.setTextSize(14);
        cancel.setGravity(android.view.Gravity.CENTER);
        cancel.setPadding(0, dp(16), 0, dp(4));
        cancel.setOnClickListener(v -> dialog.dismiss());
        layout.addView(cancel);

        dialog.setView(layout);
        dialog.setOnDismissListener(d -> {
            if (ticketIdForAfterPhoto == ticketId && afterCameraUri == null) {
                // Заявка останется выбранной только на время выбора фото.
            }
        });
        dialog.show();
    }

    private MaterialButton makeDialogButton(String text, String color) {
        MaterialButton button = new MaterialButton(this);
        button.setText(text);
        button.setTextSize(14);
        button.setTextColor(Color.WHITE);
        button.setTypeface(null, android.graphics.Typeface.BOLD);
        button.setAllCaps(false);
        button.setCornerRadius(dp(17));
        button.setBackgroundTintList(ColorStateList.valueOf(Color.parseColor(color)));
        return button;
    }

    private void openAfterPhotoGallery(int ticketId) {
        ticketIdForAfterPhoto = ticketId;
        Intent intent = new Intent(Intent.ACTION_GET_CONTENT);
        intent.setType("image/*");
        startActivityForResult(Intent.createChooser(intent, "Фото результата"), PICK_AFTER_PHOTO_REQUEST);
    }

    private void openAfterPhotoCamera(int ticketId) {
        ticketIdForAfterPhoto = ticketId;
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.CAMERA) != PackageManager.PERMISSION_GRANTED) {
            requestPermissions(new String[]{Manifest.permission.CAMERA}, AFTER_CAMERA_PERMISSION_REQUEST);
            return;
        }

        try {
            afterCameraFile = createImageFile("after_photo_");
            afterCameraUri = FileProvider.getUriForFile(
                    this,
                    getPackageName() + ".fileprovider",
                    afterCameraFile
            );

            Intent intent = new Intent(MediaStore.ACTION_IMAGE_CAPTURE);
            intent.putExtra(MediaStore.EXTRA_OUTPUT, afterCameraUri);
            intent.addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION | Intent.FLAG_GRANT_WRITE_URI_PERMISSION);

            if (intent.resolveActivity(getPackageManager()) == null) {
                Toast.makeText(this, "Камера недоступна", Toast.LENGTH_LONG).show();
                return;
            }

            startActivityForResult(intent, TAKE_AFTER_PHOTO_REQUEST);
        } catch (Exception e) {
            Toast.makeText(this, "Не удалось открыть камеру", Toast.LENGTH_LONG).show();
        }
    }

    @Override
    public void onRequestPermissionsResult(int requestCode, @NonNull String[] permissions, @NonNull int[] grantResults) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults);
        if (requestCode == AFTER_CAMERA_PERMISSION_REQUEST) {
            boolean granted = grantResults.length > 0 && grantResults[0] == PackageManager.PERMISSION_GRANTED;
            if (granted && ticketIdForAfterPhoto > 0) {
                openAfterPhotoCamera(ticketIdForAfterPhoto);
            } else {
                ticketIdForAfterPhoto = -1;
                Toast.makeText(this, "Разрешение на камеру не выдано", Toast.LENGTH_LONG).show();
            }
        }
    }

    private void openNavigation(Ticket ticket) {
        if (ticket.getLat() == null || ticket.getLng() == null) {
            Toast.makeText(this, "Координаты не указаны", Toast.LENGTH_SHORT).show();
            return;
        }

        String query = ticket.getLat() + "," + ticket.getLng();
        Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse("google.navigation:q=" + query));
        intent.setPackage("com.google.android.apps.maps");

        try {
            startActivity(intent);
        } catch (Exception ignored) {
            Intent fallback = new Intent(Intent.ACTION_VIEW, Uri.parse("geo:0,0?q=" + query));
            startActivity(fallback);
        }
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, @Nullable Intent data) {
        super.onActivityResult(requestCode, resultCode, data);

        if (requestCode == PICK_AFTER_PHOTO_REQUEST && resultCode == RESULT_OK && data != null && data.getData() != null) {
            completeTicketWithPhoto(data.getData());
            return;
        }

        if (requestCode == TAKE_AFTER_PHOTO_REQUEST && resultCode == RESULT_OK && afterCameraUri != null) {
            completeTicketWithPhoto(afterCameraUri);
            return;
        }

        if (requestCode == TAKE_AFTER_PHOTO_REQUEST) {
            afterCameraUri = null;
            afterCameraFile = null;
        }
    }

    private void completeTicketWithPhoto(Uri photoUri) {
        if (ticketIdForAfterPhoto <= 0) {
            Toast.makeText(this, "Заявка не выбрана", Toast.LENGTH_SHORT).show();
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
            RequestBody commentBody = RequestBody.create(MediaType.parse("text/plain"), completionComment);

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
                    completionComment = "Работа выполнена";
                    afterCameraUri = null;
                    afterCameraFile = null;
                    if (response.isSuccessful()) {
                        Toast.makeText(TicketListActivity.this, "Задача закрыта", Toast.LENGTH_LONG).show();
                        currentFilter = "done";
                        setupWorkerTabs();
                        loadTickets();
                    } else {
                        Toast.makeText(TicketListActivity.this, "Не удалось закрыть задачу", Toast.LENGTH_LONG).show();
                    }
                }

                @Override
                public void onFailure(Call<CreateTicketResponse> call, Throwable t) {
                    progressBar.setVisibility(View.GONE);
                    ticketIdForAfterPhoto = -1;
                    completionComment = "Работа выполнена";
                    afterCameraUri = null;
                    afterCameraFile = null;
                    Toast.makeText(TicketListActivity.this, "Ошибка подключения: " + t.getMessage(), Toast.LENGTH_LONG).show();
                }
            });
        } catch (IOException e) {
            Toast.makeText(this, "Не удалось прочитать фото", Toast.LENGTH_LONG).show();
        }
    }

    private File createImageFile(String prefix) throws IOException {
        File dir = new File(getCacheDir(), "photos");
        if (!dir.exists()) {
            dir.mkdirs();
        }
        return File.createTempFile(prefix + System.currentTimeMillis(), ".jpg", dir);
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

    @SuppressWarnings("unused")
    private String getFileName(Uri uri) {
        String result = "изображение";
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

    private String safeText(String value, String fallback) {
        return value == null || value.trim().isEmpty() ? fallback : value;
    }

    private int dp(int value) {
        return (int) (value * getResources().getDisplayMetrics().density);
    }
}
