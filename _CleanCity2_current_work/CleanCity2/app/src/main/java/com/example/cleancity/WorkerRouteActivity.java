package com.example.cleancity;

import android.Manifest;
import android.annotation.SuppressLint;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.pm.PackageManager;
import android.graphics.Color;
import android.location.Location;
import android.location.LocationManager;
import android.net.Uri;
import android.os.Bundle;
import android.view.Gravity;
import android.view.View;
import android.widget.CheckBox;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;

import com.example.cleancity.api.ApiClient;
import com.example.cleancity.api.ApiService;
import com.example.cleancity.models.Category;
import com.example.cleancity.models.Ticket;
import com.example.cleancity.models.TicketsResponse;
import com.example.cleancity.ui.AppUi;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.card.MaterialCardView;

import java.util.ArrayList;
import java.util.Collections;
import java.util.HashSet;
import java.util.List;
import java.util.Locale;
import java.util.Set;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class WorkerRouteActivity extends AppCompatActivity {

    private static final int LOCATION_PERMISSION_REQUEST = 9101;

    private LinearLayout taskSelectionContainer;
    private LinearLayout routeStopsContainer;
    private ProgressBar routeProgressBar;
    private TextView routeSummaryTextView;
    private TextView routeSubtitleTextView;
    private TextView selectedCountTextView;
    private MaterialButton backButton;
    private MaterialButton selectAllButton;
    private MaterialButton optimizeRouteButton;
    private MaterialButton openRouteButton;
    private MaterialButton refreshRouteButton;
    private TextView navHomeTextView;
    private TextView navTasksTextView;
    private TextView navRouteTextView;
    private TextView navMapTextView;
    private TextView navProfileTextView;

    private ApiService apiService;
    private String token;

    private final List<Ticket> activeTickets = new ArrayList<>();
    private final List<RouteStop> optimizedStops = new ArrayList<>();
    private final Set<Integer> selectedTicketIds = new HashSet<>();

    private Double currentLat;
    private Double currentLng;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        AppUi.applyTheme(this);
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_worker_route);
        AppUi.applyAll(this, "Маршрут", "Маршрут");

        taskSelectionContainer = findViewById(R.id.taskSelectionContainer);
        routeStopsContainer = findViewById(R.id.routeStopsContainer);
        routeProgressBar = findViewById(R.id.routeProgressBar);
        routeSummaryTextView = findViewById(R.id.routeSummaryTextView);
        routeSubtitleTextView = findViewById(R.id.routeSubtitleTextView);
        selectedCountTextView = findViewById(R.id.selectedCountTextView);
        backButton = findViewById(R.id.routeBackButton);
        selectAllButton = findViewById(R.id.selectAllButton);
        optimizeRouteButton = findViewById(R.id.optimizeRouteButton);
        openRouteButton = findViewById(R.id.openRouteButton);
        refreshRouteButton = findViewById(R.id.refreshRouteButton);
        navHomeTextView = findViewById(R.id.navHomeTextView);
        navTasksTextView = findViewById(R.id.navTasksTextView);
        navRouteTextView = findViewById(R.id.navRouteTextView);
        navMapTextView = findViewById(R.id.navMapTextView);
        navProfileTextView = findViewById(R.id.navProfileTextView);

        SharedPreferences preferences = getSharedPreferences("auth", MODE_PRIVATE);
        String serverUrl = preferences.getString("server_url", ApiClient.DEFAULT_BASE_URL);
        token = preferences.getString("token", "");
        apiService = ApiClient.getClient(serverUrl).create(ApiService.class);

        backButton.setOnClickListener(v -> finish());
        selectAllButton.setOnClickListener(v -> toggleAllSelection());
        optimizeRouteButton.setOnClickListener(v -> rebuildOptimizedRoute(true));
        openRouteButton.setOnClickListener(v -> openOptimizedRouteInMaps());
        refreshRouteButton.setOnClickListener(v -> loadTickets());
        setupBottomNav("route");

        requestLocationIfNeeded();
        loadTickets();
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
        navTasksTextView.setOnClickListener(v -> {
            AppUi.feedback(this, v);
            Intent intent = new Intent(this, TicketListActivity.class);
            intent.putExtra("mode", "worker");
            startActivity(intent);
        });
        navRouteTextView.setOnClickListener(v -> { AppUi.feedback(this, v); });
        navMapTextView.setOnClickListener(v -> { AppUi.feedback(this, v); startActivity(new Intent(this, WorkerMapActivity.class)); });
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

    private void requestLocationIfNeeded() {
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED
                || ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_COARSE_LOCATION) == PackageManager.PERMISSION_GRANTED) {
            readLastLocation();
            return;
        }

        ActivityCompat.requestPermissions(
                this,
                new String[]{Manifest.permission.ACCESS_FINE_LOCATION, Manifest.permission.ACCESS_COARSE_LOCATION},
                LOCATION_PERMISSION_REQUEST
        );
    }

    @Override
    public void onRequestPermissionsResult(int requestCode, @NonNull String[] permissions, @NonNull int[] grantResults) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults);
        if (requestCode == LOCATION_PERMISSION_REQUEST) {
            readLastLocation();
        }
    }

    @SuppressLint("MissingPermission")
    private void readLastLocation() {
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) != PackageManager.PERMISSION_GRANTED
                && ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_COARSE_LOCATION) != PackageManager.PERMISSION_GRANTED) {
            return;
        }

        try {
            LocationManager locationManager = (LocationManager) getSystemService(LOCATION_SERVICE);
            if (locationManager == null) {
                return;
            }

            Location gps = locationManager.getLastKnownLocation(LocationManager.GPS_PROVIDER);
            Location network = locationManager.getLastKnownLocation(LocationManager.NETWORK_PROVIDER);
            Location best = chooseBestLocation(gps, network);
            if (best != null) {
                currentLat = best.getLatitude();
                currentLng = best.getLongitude();
                rebuildOptimizedRoute(false);
            }
        } catch (Exception ignored) {
            // Точка старта необязательна.
        }
    }

    private Location chooseBestLocation(Location first, Location second) {
        if (first == null) return second;
        if (second == null) return first;
        return first.getTime() >= second.getTime() ? first : second;
    }

    private void loadTickets() {
        routeProgressBar.setVisibility(View.VISIBLE);
        routeSummaryTextView.setText("Загрузка задач...");
        taskSelectionContainer.removeAllViews();
        routeStopsContainer.removeAllViews();

        apiService.getWorkerTickets("Bearer " + token).enqueue(new Callback<TicketsResponse>() {
            @Override
            public void onResponse(Call<TicketsResponse> call, Response<TicketsResponse> response) {
                routeProgressBar.setVisibility(View.GONE);
                if (!response.isSuccessful() || response.body() == null) {
                    Toast.makeText(WorkerRouteActivity.this, "Не удалось загрузить задачи", Toast.LENGTH_LONG).show();
                    showEmptyState("Задачи не загружены");
                    return;
                }

                activeTickets.clear();
                selectedTicketIds.clear();

                List<Ticket> tickets = response.body().getTickets();
                if (tickets != null) {
                    for (Ticket ticket : tickets) {
                        if (isRouteTicket(ticket)) {
                            activeTickets.add(ticket);
                            selectedTicketIds.add(ticket.getId());
                        }
                    }
                }

                updateSelectionUi();
                rebuildOptimizedRoute(false);
            }

            @Override
            public void onFailure(Call<TicketsResponse> call, Throwable t) {
                routeProgressBar.setVisibility(View.GONE);
                Toast.makeText(WorkerRouteActivity.this, "Ошибка подключения: " + t.getMessage(), Toast.LENGTH_LONG).show();
                showEmptyState("Нет подключения");
            }
        });
    }

    private boolean isRouteTicket(Ticket ticket) {
        if (ticket == null || ticket.getLat() == null || ticket.getLng() == null) {
            return false;
        }

        Double lat = parseDouble(ticket.getLat());
        Double lng = parseDouble(ticket.getLng());
        if (lat == null || lng == null) {
            return false;
        }

        String status = ticket.getStatus();
        return "assigned".equals(status)
                || "accepted".equals(status)
                || "in_progress".equals(status)
                || "problem".equals(status)
                || "postponed".equals(status);
    }

    private void toggleAllSelection() {
        if (activeTickets.isEmpty()) {
            return;
        }

        boolean shouldSelectAll = selectedTicketIds.size() != activeTickets.size();
        selectedTicketIds.clear();
        if (shouldSelectAll) {
            for (Ticket ticket : activeTickets) {
                selectedTicketIds.add(ticket.getId());
            }
        }
        updateSelectionUi();
        rebuildOptimizedRoute(false);
    }

    private void updateSelectionUi() {
        boolean allSelected = !activeTickets.isEmpty() && selectedTicketIds.size() == activeTickets.size();
        selectAllButton.setText(allSelected ? "Снять все" : "Выбрать все");
        selectedCountTextView.setText(selectedTicketIds.size() + " из " + activeTickets.size());
        renderTaskSelectionList();
    }

    private void renderTaskSelectionList() {
        taskSelectionContainer.removeAllViews();

        if (activeTickets.isEmpty()) {
            TextView empty = new TextView(this);
            empty.setText("Активных задач с координатами нет");
            empty.setTextColor(Color.parseColor("#6B7280"));
            empty.setTextSize(14);
            empty.setPadding(0, dp(12), 0, dp(12));
            taskSelectionContainer.addView(empty);
            AppUi.apply(this);
            return;
        }

        for (Ticket ticket : activeTickets) {
            taskSelectionContainer.addView(createSelectableTaskCard(ticket));
        }
        AppUi.apply(this);
    }

    private View createSelectableTaskCard(Ticket ticket) {
        MaterialCardView card = new MaterialCardView(this);
        boolean selected = selectedTicketIds.contains(ticket.getId());
        card.setRadius(dp(20));
        card.setCardElevation(dp(1));
        card.setCardBackgroundColor(selected ? Color.parseColor("#E8F7EE") : Color.WHITE);
        card.setStrokeColor(selected ? Color.parseColor("#146C43") : Color.parseColor("#DDE7E1"));
        card.setStrokeWidth(dp(1));
        card.setContentPadding(dp(14), dp(12), dp(14), dp(12));
        LinearLayout.LayoutParams cardParams = new LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
        );
        cardParams.setMargins(0, 0, 0, dp(10));
        card.setLayoutParams(cardParams);

        LinearLayout row = new LinearLayout(this);
        row.setOrientation(LinearLayout.HORIZONTAL);
        row.setGravity(Gravity.CENTER_VERTICAL);

        CheckBox checkBox = new CheckBox(this);
        checkBox.setChecked(selected);
        checkBox.setButtonTintList(android.content.res.ColorStateList.valueOf(Color.parseColor("#146C43")));
        checkBox.setOnCheckedChangeListener((buttonView, isChecked) -> toggleTicket(ticket.getId(), isChecked));
        row.addView(checkBox);

        LinearLayout textWrap = new LinearLayout(this);
        textWrap.setOrientation(LinearLayout.VERTICAL);
        LinearLayout.LayoutParams textParams = new LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1);
        textWrap.setLayoutParams(textParams);

        TextView title = new TextView(this);
        title.setText("№" + ticket.getId() + " · " + categoryName(ticket));
        title.setTextColor(Color.parseColor("#1F2933"));
        title.setTextSize(15);
        title.setTypeface(null, android.graphics.Typeface.BOLD);
        textWrap.addView(title);

        TextView details = new TextView(this);
        details.setText(ticket.getStatusLabel() + " · " + shortLocation(ticket));
        details.setTextColor(Color.parseColor("#6B7280"));
        details.setTextSize(12);
        details.setMaxLines(2);
        details.setPadding(0, dp(4), 0, 0);
        textWrap.addView(details);

        row.addView(textWrap);
        card.addView(row);
        card.setOnClickListener(v -> toggleTicket(ticket.getId(), !selectedTicketIds.contains(ticket.getId())));
        return card;
    }

    private void toggleTicket(int ticketId, boolean selected) {
        if (selected) {
            selectedTicketIds.add(ticketId);
        } else {
            selectedTicketIds.remove(ticketId);
        }
        updateSelectionUi();
        rebuildOptimizedRoute(false);
    }

    private void rebuildOptimizedRoute(boolean showToast) {
        optimizedStops.clear();

        List<RouteStop> selectedStops = new ArrayList<>();
        for (Ticket ticket : activeTickets) {
            if (!selectedTicketIds.contains(ticket.getId())) {
                continue;
            }
            Double lat = parseDouble(ticket.getLat());
            Double lng = parseDouble(ticket.getLng());
            if (lat != null && lng != null) {
                selectedStops.add(new RouteStop(ticket, lat, lng));
            }
        }

        if (selectedStops.isEmpty()) {
            showEmptyState(activeTickets.isEmpty() ? "Нет задач для маршрута" : "Выберите задачи для маршрута");
            return;
        }

        optimizedStops.addAll(optimizeRoute(selectedStops));
        renderStopsList();
        updateSummary();

        if (showToast) {
            Toast.makeText(this, "Порядок маршрута обновлён", Toast.LENGTH_SHORT).show();
        }
    }

    private List<RouteStop> optimizeRoute(List<RouteStop> source) {
        if (source.size() <= 2) {
            return new ArrayList<>(source);
        }

        List<RouteStop> unvisited = new ArrayList<>(source);
        List<RouteStop> route = new ArrayList<>();

        double fromLat;
        double fromLng;
        if (currentLat != null && currentLng != null) {
            fromLat = currentLat;
            fromLng = currentLng;
        } else {
            RouteStop first = unvisited.remove(0);
            route.add(first);
            fromLat = first.lat;
            fromLng = first.lng;
        }

        while (!unvisited.isEmpty()) {
            int nearestIndex = 0;
            double nearestDistance = Double.MAX_VALUE;
            for (int i = 0; i < unvisited.size(); i++) {
                RouteStop candidate = unvisited.get(i);
                double distance = distanceKm(fromLat, fromLng, candidate.lat, candidate.lng);
                if (distance < nearestDistance) {
                    nearestDistance = distance;
                    nearestIndex = i;
                }
            }
            RouteStop next = unvisited.remove(nearestIndex);
            route.add(next);
            fromLat = next.lat;
            fromLng = next.lng;
        }

        return twoOpt(route);
    }

    private List<RouteStop> twoOpt(List<RouteStop> input) {
        List<RouteStop> route = new ArrayList<>(input);
        if (route.size() < 4) {
            return route;
        }

        boolean improved = true;
        int iterations = 0;
        while (improved && iterations < 60) {
            improved = false;
            iterations++;

            for (int i = 0; i < route.size() - 2; i++) {
                for (int k = i + 1; k < route.size() - 1; k++) {
                    double before = routeDistance(route);
                    Collections.reverse(route.subList(i, k + 1));
                    double after = routeDistance(route);
                    if (after + 0.001 < before) {
                        improved = true;
                    } else {
                        Collections.reverse(route.subList(i, k + 1));
                    }
                }
            }
        }
        return route;
    }

    private double routeDistance(List<RouteStop> route) {
        if (route.isEmpty()) return 0;

        double total = 0;
        double fromLat;
        double fromLng;
        int startIndex = 0;

        if (currentLat != null && currentLng != null) {
            fromLat = currentLat;
            fromLng = currentLng;
        } else {
            fromLat = route.get(0).lat;
            fromLng = route.get(0).lng;
            startIndex = 1;
        }

        for (int i = startIndex; i < route.size(); i++) {
            RouteStop stop = route.get(i);
            total += distanceKm(fromLat, fromLng, stop.lat, stop.lng);
            fromLat = stop.lat;
            fromLng = stop.lng;
        }
        return total;
    }

    private void renderStopsList() {
        routeStopsContainer.removeAllViews();

        for (int i = 0; i < optimizedStops.size(); i++) {
            RouteStop stop = optimizedStops.get(i);
            routeStopsContainer.addView(createStopCard(stop, i + 1));
        }
        AppUi.apply(this);
    }

    private View createStopCard(RouteStop stop, int order) {
        MaterialCardView card = new MaterialCardView(this);
        card.setRadius(dp(20));
        card.setCardElevation(dp(1));
        card.setCardBackgroundColor(Color.WHITE);
        card.setContentPadding(dp(14), dp(12), dp(14), dp(12));
        LinearLayout.LayoutParams cardParams = new LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT
        );
        cardParams.setMargins(0, 0, 0, dp(10));
        card.setLayoutParams(cardParams);

        LinearLayout row = new LinearLayout(this);
        row.setOrientation(LinearLayout.HORIZONTAL);
        row.setGravity(Gravity.CENTER_VERTICAL);

        TextView number = new TextView(this);
        number.setText(String.valueOf(order));
        number.setTextColor(Color.WHITE);
        number.setTextSize(14);
        number.setTypeface(null, android.graphics.Typeface.BOLD);
        number.setGravity(Gravity.CENTER);
        number.setBackgroundResource(R.drawable.bg_route_number_circle);
        LinearLayout.LayoutParams numberParams = new LinearLayout.LayoutParams(dp(36), dp(36));
        numberParams.setMargins(0, 0, dp(12), 0);
        number.setLayoutParams(numberParams);
        row.addView(number);

        LinearLayout textWrap = new LinearLayout(this);
        textWrap.setOrientation(LinearLayout.VERTICAL);
        LinearLayout.LayoutParams textParams = new LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1);
        textWrap.setLayoutParams(textParams);

        TextView title = new TextView(this);
        title.setText("№" + stop.ticket.getId() + " · " + categoryName(stop.ticket));
        title.setTextColor(Color.parseColor("#1F2933"));
        title.setTextSize(14);
        title.setTypeface(null, android.graphics.Typeface.BOLD);
        textWrap.addView(title);

        TextView details = new TextView(this);
        details.setText(shortLocation(stop.ticket));
        details.setTextColor(Color.parseColor("#6B7280"));
        details.setTextSize(12);
        details.setPadding(0, dp(3), 0, 0);
        textWrap.addView(details);

        row.addView(textWrap);
        card.addView(row);
        return card;
    }

    private void updateSummary() {
        double distance = routeDistance(optimizedStops);
        String startText = currentLat != null && currentLng != null ? "от текущей точки" : "от первой задачи";
        routeSubtitleTextView.setText("Порядок задач для Google Maps");
        routeSummaryTextView.setText("Выбрано: " + optimizedStops.size() + " · " + startText + " · примерно " + String.format(Locale.US, "%.1f", distance) + " км");
    }

    private void showEmptyState(String text) {
        optimizedStops.clear();
        routeStopsContainer.removeAllViews();
        routeSummaryTextView.setText(text);
        routeSubtitleTextView.setText("Выберите задачи для маршрута");
        AppUi.apply(this);
    }

    private void openOptimizedRouteInMaps() {
        if (optimizedStops.isEmpty()) {
            Toast.makeText(this, "Сначала выберите задачи", Toast.LENGTH_SHORT).show();
            return;
        }

        if (optimizedStops.size() == 1) {
            RouteStop only = optimizedStops.get(0);
            openSinglePoint(only.lat, only.lng);
            return;
        }

        String origin;
        int firstStopIndex = 0;
        if (currentLat != null && currentLng != null) {
            origin = format(currentLat) + "," + format(currentLng);
        } else {
            RouteStop first = optimizedStops.get(0);
            origin = format(first.lat) + "," + format(first.lng);
            firstStopIndex = 1;
        }

        RouteStop last = optimizedStops.get(optimizedStops.size() - 1);
        String destination = format(last.lat) + "," + format(last.lng);
        List<String> waypoints = new ArrayList<>();
        for (int i = firstStopIndex; i < optimizedStops.size() - 1; i++) {
            RouteStop stop = optimizedStops.get(i);
            waypoints.add(format(stop.lat) + "," + format(stop.lng));
        }

        StringBuilder url = new StringBuilder("https://www.google.com/maps/dir/?api=1&travelmode=driving");
        url.append("&origin=").append(Uri.encode(origin));
        url.append("&destination=").append(Uri.encode(destination));
        if (!waypoints.isEmpty()) {
            url.append("&waypoints=").append(Uri.encode(joinWaypoints(waypoints)));
        }

        Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse(url.toString()));
        try {
            startActivity(intent);
        } catch (Exception e) {
            Toast.makeText(this, "Не удалось открыть Google Maps", Toast.LENGTH_LONG).show();
        }
    }

    private void openSinglePoint(double lat, double lng) {
        String query = format(lat) + "," + format(lng);
        Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse("google.navigation:q=" + query));
        intent.setPackage("com.google.android.apps.maps");
        try {
            startActivity(intent);
        } catch (Exception ignored) {
            startActivity(new Intent(Intent.ACTION_VIEW, Uri.parse("geo:0,0?q=" + query)));
        }
    }

    private String joinWaypoints(List<String> waypoints) {
        StringBuilder builder = new StringBuilder();
        for (int i = 0; i < waypoints.size(); i++) {
            if (i > 0) builder.append("|");
            builder.append(waypoints.get(i));
        }
        return builder.toString();
    }

    private String categoryName(Ticket ticket) {
        Category category = ticket.getCategory();
        return category != null && category.getName() != null ? category.getName() : "Категория";
    }

    private String shortLocation(Ticket ticket) {
        if (ticket.getAddressText() != null && !ticket.getAddressText().trim().isEmpty()) {
            return ticket.getAddressText().trim();
        }
        return "Координаты: " + ticket.getLat() + ", " + ticket.getLng();
    }

    private Double parseDouble(String value) {
        try {
            if (value == null) return null;
            return Double.parseDouble(value.replace(",", "."));
        } catch (Exception e) {
            return null;
        }
    }

    private double distanceKm(double lat1, double lng1, double lat2, double lng2) {
        double earthRadius = 6371.0;
        double dLat = Math.toRadians(lat2 - lat1);
        double dLng = Math.toRadians(lng2 - lng1);
        double a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
                + Math.cos(Math.toRadians(lat1)) * Math.cos(Math.toRadians(lat2))
                * Math.sin(dLng / 2) * Math.sin(dLng / 2);
        double c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return earthRadius * c;
    }

    private String format(double value) {
        return String.format(Locale.US, "%.6f", value);
    }

    private int dp(int value) {
        return (int) (value * getResources().getDisplayMetrics().density);
    }

    private static class RouteStop {
        final Ticket ticket;
        final double lat;
        final double lng;

        RouteStop(Ticket ticket, double lat, double lng) {
            this.ticket = ticket;
            this.lat = lat;
            this.lng = lng;
        }
    }
}
