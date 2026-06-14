package com.example.cleancity;

import android.annotation.SuppressLint;
import android.content.Intent;
import android.content.SharedPreferences;
import android.graphics.Color;
import android.net.Uri;
import android.os.Bundle;
import android.view.View;
import android.webkit.JavascriptInterface;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.example.cleancity.api.ApiClient;
import com.example.cleancity.api.ApiService;
import com.example.cleancity.models.Category;
import com.example.cleancity.models.SimpleResponse;
import com.example.cleancity.models.Ticket;
import com.example.cleancity.models.TicketsResponse;
import com.example.cleancity.ui.AppUi;
import com.google.android.material.button.MaterialButton;

import java.util.ArrayList;
import java.util.HashSet;
import java.util.List;
import java.util.Locale;
import java.util.Set;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class WorkerMapActivity extends AppCompatActivity {

    private WebView mapWebView;
    private ProgressBar mapProgressBar;
    private TextView mapSummaryTextView;
    private MaterialButton backButton;
    private TextView navHomeTextView;
    private TextView navTasksTextView;
    private TextView navRouteTextView;
    private TextView navMapTextView;
    private TextView navProfileTextView;

    private ApiService apiService;
    private String token;
    private final List<MapTicket> mapTickets = new ArrayList<>();

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        AppUi.applyTheme(this);
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_worker_map);
        AppUi.applyAll(this, "Карта", "Карта");

        mapWebView = findViewById(R.id.workerMapWebView);
        mapProgressBar = findViewById(R.id.workerMapProgressBar);
        mapSummaryTextView = findViewById(R.id.workerMapSummaryTextView);
        backButton = findViewById(R.id.mapBackButton);
        navHomeTextView = findViewById(R.id.navHomeTextView);
        navTasksTextView = findViewById(R.id.navTasksTextView);
        navRouteTextView = findViewById(R.id.navRouteTextView);
        navMapTextView = findViewById(R.id.navMapTextView);
        navProfileTextView = findViewById(R.id.navProfileTextView);

        backButton.setOnClickListener(v -> finish());
        setupBottomNav("map");
        setupWebView();

        SharedPreferences preferences = getSharedPreferences("auth", MODE_PRIVATE);
        String serverUrl = preferences.getString("server_url", ApiClient.DEFAULT_BASE_URL);
        token = preferences.getString("token", "");
        apiService = ApiClient.getClient(serverUrl).create(ApiService.class);

        loadTickets();
    }

    @SuppressLint({"SetJavaScriptEnabled", "AddJavascriptInterface"})
    private void setupWebView() {
        WebSettings settings = mapWebView.getSettings();
        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setLoadWithOverviewMode(true);
        settings.setUseWideViewPort(true);
        mapWebView.setBackgroundColor(Color.TRANSPARENT);
        mapWebView.addJavascriptInterface(new MapBridge(), "Android");
    }

    private void loadTickets() {
        mapProgressBar.setVisibility(View.VISIBLE);
        mapSummaryTextView.setText("Загрузка карты...");
        mapTickets.clear();

        apiService.getWorkerTickets("Bearer " + token).enqueue(new Callback<TicketsResponse>() {
            @Override
            public void onResponse(Call<TicketsResponse> call, Response<TicketsResponse> response) {
                if (!response.isSuccessful() || response.body() == null) {
                    mapProgressBar.setVisibility(View.GONE);
                    showEmptyMap("Задачи не загружены");
                    Toast.makeText(WorkerMapActivity.this, "Не удалось загрузить задачи", Toast.LENGTH_LONG).show();
                    return;
                }

                Set<Integer> added = new HashSet<>();
                List<Ticket> tickets = response.body().getTickets();
                if (tickets != null) {
                    for (Ticket ticket : tickets) {
                        if (hasLocation(ticket) && !"completed".equals(ticket.getStatus())) {
                            mapTickets.add(new MapTicket(ticket, false));
                            added.add(ticket.getId());
                        }
                    }
                }

                loadAvailableTickets(added);
            }

            @Override
            public void onFailure(Call<TicketsResponse> call, Throwable t) {
                mapProgressBar.setVisibility(View.GONE);
                Toast.makeText(WorkerMapActivity.this, "Ошибка подключения: " + t.getMessage(), Toast.LENGTH_LONG).show();
                showEmptyMap("Нет подключения");
            }
        });
    }

    private void loadAvailableTickets(Set<Integer> existingIds) {
        apiService.getAvailableTickets("Bearer " + token).enqueue(new Callback<TicketsResponse>() {
            @Override
            public void onResponse(Call<TicketsResponse> call, Response<TicketsResponse> response) {
                mapProgressBar.setVisibility(View.GONE);

                if (response.isSuccessful() && response.body() != null && response.body().getTickets() != null) {
                    for (Ticket ticket : response.body().getTickets()) {
                        if (hasLocation(ticket) && !existingIds.contains(ticket.getId())) {
                            mapTickets.add(new MapTicket(ticket, true));
                        }
                    }
                }

                renderMap();
            }

            @Override
            public void onFailure(Call<TicketsResponse> call, Throwable t) {
                mapProgressBar.setVisibility(View.GONE);
                renderMap();
            }
        });
    }

    private void renderMap() {
        int required = 0;
        int available = 0;
        for (MapTicket item : mapTickets) {
            if (item.available) available++; else required++;
        }

        mapSummaryTextView.setText("Обязательные: " + required + " · Доступные: " + available);

        if (mapTickets.isEmpty()) {
            showEmptyMap("Нет заявок с координатами");
            return;
        }

        mapWebView.loadDataWithBaseURL("https://worker.map/", buildMapHtml(), "text/html", "UTF-8", null);
    }

    private void showEmptyMap(String text) {
        mapWebView.loadDataWithBaseURL(
                "https://worker.map/",
                "<!DOCTYPE html><html><body style='margin:0;height:100%;display:flex;align-items:center;justify-content:center;background:#F4F8F5;font-family:sans-serif;color:#6B7280;'><div>" + htmlEscape(text) + "</div></body></html>",
                "text/html",
                "UTF-8",
                null
        );
    }

    private String buildMapHtml() {
        StringBuilder markers = new StringBuilder();
        double centerLat = 0;
        double centerLng = 0;
        int count = 0;

        for (MapTicket item : mapTickets) {
            Ticket ticket = item.ticket;
            Double lat = parseDouble(ticket.getLat());
            Double lng = parseDouble(ticket.getLng());
            if (lat == null || lng == null) continue;

            centerLat += lat;
            centerLng += lng;
            if (count > 0) markers.append(',');
            count++;

            markers.append("{id:").append(ticket.getId())
                    .append(",lat:").append(format(lat))
                    .append(",lng:").append(format(lng))
                    .append(",available:").append(item.available ? "true" : "false")
                    .append(",title:'").append(jsEscape(htmlEscape("№" + ticket.getId() + " · " + categoryName(ticket)))).append("'")
                    .append(",status:'").append(jsEscape(htmlEscape(item.available ? "Доступная" : ticket.getStatusLabel()))).append("'")
                    .append(",comment:'").append(jsEscape(htmlEscape(commentText(ticket)))).append("'")
                    .append(",color:'").append(item.available ? "#f59e0b" : markerColor(ticket.getStatus())).append("'")
                    .append("}");
        }

        if (count == 0) {
            centerLat = 53.1384;
            centerLng = 29.2214;
        } else {
            centerLat /= count;
            centerLng /= count;
        }

        return "<!DOCTYPE html><html><head><meta name='viewport' content='width=device-width, initial-scale=1.0'>"
                + "<link rel='stylesheet' href='https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'/>"
                + "<script src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'></script>"
                + "<style>html,body,#map{height:100%;margin:0;}"
                + ".pinWrap{width:40px;height:52px;position:relative}.pinBody{position:absolute;left:5px;top:0;width:30px;height:30px;border-radius:20px 20px 20px 6px;transform:rotate(-45deg);border:3px solid white;box-shadow:0 6px 18px rgba(0,0,0,.32)}.pinDot{position:absolute;left:16px;top:11px;width:8px;height:8px;border-radius:50%;background:white}"
                + ".pop{font:13px/1.35 sans-serif;color:#1F2933;min-width:220px}.pop b{font-size:15px}.badge{display:inline-block;margin-top:7px;background:#E7EFEA;color:#0F5132;border-radius:12px;padding:5px 9px;font-weight:700}.badge.available{background:#fff7ed;color:#9a3412}.comment{display:none;margin-top:9px;color:#4B5563;background:#F4F8F5;border-radius:12px;padding:9px}.toggle{margin-top:9px;background:#F4F8F5;color:#1F2933;border:0;border-radius:12px;padding:8px 10px;font-weight:700}.actions{display:flex;gap:7px;margin-top:10px;flex-wrap:wrap}.btn{border:0;border-radius:12px;padding:9px 10px;font-weight:700}.open{background:#146C43;color:white}.route{background:#E7EFEA;color:#0F5132}.claim{background:#f59e0b;color:white}"
                + ".legend{position:absolute;left:12px;right:12px;bottom:12px;z-index:999;background:white;border-radius:18px;padding:10px 12px;box-shadow:0 4px 18px rgba(0,0,0,.12);font:12px sans-serif;color:#6B7280;display:flex;gap:12px}.dot{width:9px;height:9px;border-radius:50%;display:inline-block;margin-right:5px}.green{background:#146C43}.orange{background:#f59e0b}"
                + "</style></head><body><div id='map'></div><div class='legend'><span><i class='dot green'></i>Обязательные</span><span><i class='dot orange'></i>Доступные</span></div><script>"
                + "var map=L.map('map').setView([" + format(centerLat) + "," + format(centerLng) + "], 12);"
                + "L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:''}).addTo(map);"
                + "function toggleComment(id){var el=document.getElementById('comment_'+id); if(el){el.style.display=el.style.display==='block'?'none':'block';}}"
                + "var markers=[" + markers + "]; var bounds=[];"
                + "markers.forEach(function(p){var icon=L.divIcon({className:'',iconSize:[40,52],iconAnchor:[20,49],popupAnchor:[0,-45],html:'<div class=\"pinWrap\"><div class=\"pinBody\" style=\"background:'+p.color+'\"></div><div class=\"pinDot\"></div></div>'});"
                + "var badgeClass=p.available?'badge available':'badge';"
                + "var commentHtml=p.comment?'<button class=\"toggle\" onclick=\"toggleComment('+p.id+')\">Комментарий</button><div id=\"comment_'+p.id+'\" class=\"comment\">'+p.comment+'</div>':'';"
                + "var actions=p.available?'<button class=\"btn claim\" onclick=\"Android.claimTicket('+p.id+')\">Запросить</button><button class=\"btn route\" onclick=\"Android.navigate('+p.lat+','+p.lng+')\">Маршрут</button>':'<button class=\"btn open\" onclick=\"Android.openTicket('+p.id+')\">Открыть</button><button class=\"btn route\" onclick=\"Android.navigate('+p.lat+','+p.lng+')\">Маршрут</button>';"
                + "var html='<div class=\"pop\"><b>'+p.title+'</b><div><span class=\"'+badgeClass+'\">'+p.status+'</span></div>'+commentHtml+'<div class=\"actions\">'+actions+'</div></div>';"
                + "L.marker([p.lat,p.lng],{icon:icon}).addTo(map).bindPopup(html); bounds.push([p.lat,p.lng]);});"
                + "if(bounds.length>0){try{map.fitBounds(bounds,{padding:[34,34]});}catch(e){}}"
                + "</script></body></html>";
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
        navRouteTextView.setOnClickListener(v -> { AppUi.feedback(this, v); startActivity(new Intent(this, WorkerRouteActivity.class)); });
        navMapTextView.setOnClickListener(v -> { AppUi.feedback(this, v); });
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

    private void requestTicket(int ticketId) {
        mapProgressBar.setVisibility(View.VISIBLE);
        apiService.requestTicketClaim("Bearer " + token, ticketId).enqueue(new Callback<SimpleResponse>() {
            @Override
            public void onResponse(Call<SimpleResponse> call, Response<SimpleResponse> response) {
                mapProgressBar.setVisibility(View.GONE);
                String message = response.body() != null && response.body().getMessage() != null
                        ? response.body().getMessage()
                        : (response.isSuccessful() ? "Запрос отправлен" : "Не удалось отправить запрос");
                Toast.makeText(WorkerMapActivity.this, message, Toast.LENGTH_LONG).show();
                loadTickets();
            }

            @Override
            public void onFailure(Call<SimpleResponse> call, Throwable t) {
                mapProgressBar.setVisibility(View.GONE);
                Toast.makeText(WorkerMapActivity.this, "Ошибка подключения: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    private String markerColor(String status) {
        if (status == null) return "#146C43";
        switch (status) {
            case "assigned": return "#146C43";
            case "accepted": return "#7C3AED";
            case "in_progress": return "#2563EB";
            case "completed": return "#16A34A";
            case "rejected": return "#DC2626";
            default: return "#146C43";
        }
    }

    private boolean hasLocation(Ticket ticket) {
        return ticket != null && parseDouble(ticket.getLat()) != null && parseDouble(ticket.getLng()) != null;
    }

    private String categoryName(Ticket ticket) {
        Category category = ticket.getCategory();
        return category != null && category.getName() != null ? category.getName() : "Категория";
    }

    private String commentText(Ticket ticket) {
        if (ticket.getDescription() != null && !ticket.getDescription().trim().isEmpty()) {
            return ticket.getDescription().trim();
        }
        return "";
    }

    private Double parseDouble(String value) {
        try {
            if (value == null) return null;
            return Double.parseDouble(value.replace(",", "."));
        } catch (Exception e) {
            return null;
        }
    }

    private String format(double value) {
        return String.format(Locale.US, "%.6f", value);
    }

    private String jsEscape(String value) {
        if (value == null) return "";
        return value.replace("\\", "\\\\").replace("'", "\\'").replace("\n", " ").replace("\r", " ");
    }

    private String htmlEscape(String value) {
        if (value == null) return "";
        return value.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;");
    }

    private static class MapTicket {
        final Ticket ticket;
        final boolean available;

        MapTicket(Ticket ticket, boolean available) {
            this.ticket = ticket;
            this.available = available;
        }
    }

    public class MapBridge {
        @JavascriptInterface
        public void openTicket(int ticketId) {
            runOnUiThread(() -> {
                Intent intent = new Intent(WorkerMapActivity.this, TicketListActivity.class);
                intent.putExtra("mode", "worker");
                intent.putExtra("focus_ticket_id", ticketId);
                startActivity(intent);
            });
        }

        @JavascriptInterface
        public void claimTicket(int ticketId) {
            runOnUiThread(() -> requestTicket(ticketId));
        }

        @JavascriptInterface
        public void navigate(double lat, double lng) {
            runOnUiThread(() -> {
                String query = format(lat) + "," + format(lng);
                Intent intent = new Intent(Intent.ACTION_VIEW, Uri.parse("google.navigation:q=" + query));
                intent.setPackage("com.google.android.apps.maps");
                try {
                    startActivity(intent);
                } catch (Exception ignored) {
                    startActivity(new Intent(Intent.ACTION_VIEW, Uri.parse("geo:0,0?q=" + query)));
                }
            });
        }
    }
}
