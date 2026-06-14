package com.example.cleancity.resident;

import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.graphics.Color;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.ScrollView;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;

import com.example.cleancity.R;
import com.example.cleancity.ResidentContainerActivity;
import com.example.cleancity.api.ApiClient;
import com.example.cleancity.api.ApiService;
import com.example.cleancity.models.SimpleResponse;
import com.example.cleancity.models.Ticket;
import com.example.cleancity.models.TicketsResponse;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.card.MaterialCardView;

import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.List;
import java.util.Locale;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

/**
 * Shows tickets marked "available_to_residents" = true by admin.
 * Residents can volunteer to accept and complete these tasks.
 */
public class ResidentAvailableTasksFragment extends Fragment {

    private LinearLayout tasksContainer;
    private ProgressBar progressBar;
    private ApiService apiService;
    private String token;

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        return inflater.inflate(R.layout.activity_resident_tasks, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View v, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(v, savedInstanceState);

        hideId(v, R.id.bottomNavContainer);
        hideId(v, R.id.residentTasksBackButton);
        hideId(v, R.id.tabMine); // hide "мои" tab — show only available

        tasksContainer = v.findViewById(R.id.residentTasksContainer);
        progressBar    = v.findViewById(R.id.residentTasksProgressBar);

        SharedPreferences prefs = requireContext().getSharedPreferences("auth", Context.MODE_PRIVATE);
        token = prefs.getString("token", "");
        String serverUrl = prefs.getString("server_url", ApiClient.DEFAULT_BASE_URL);
        apiService = ApiClient.getClient(serverUrl).create(ApiService.class);

        loadTasks();
    }

    @Override
    public void onResume() { super.onResume(); loadTasks(); }

    private void loadTasks() {
        if (token == null || token.isEmpty()) return;
        if (progressBar != null) progressBar.setVisibility(View.VISIBLE);
        apiService.getResidentAvailableTasks("Bearer " + token).enqueue(new Callback<TicketsResponse>() {
            @Override
            public void onResponse(Call<TicketsResponse> c, Response<TicketsResponse> r) {
                if (!isAdded()) return;
                if (progressBar != null) progressBar.setVisibility(View.GONE);
                if (r.isSuccessful() && r.body() != null && r.body().getTickets() != null) {
                    showTasks(r.body().getTickets());
                } else {
                    showEmpty("Задач пока нет");
                }
            }
            @Override
            public void onFailure(Call<TicketsResponse> c, Throwable t) {
                if (!isAdded()) return;
                if (progressBar != null) progressBar.setVisibility(View.GONE);
                showEmpty("Ошибка подключения");
            }
        });
    }

    private void showTasks(List<Ticket> tasks) {
        if (tasksContainer == null) return;
        tasksContainer.removeAllViews();
        if (tasks.isEmpty()) { showEmpty("Доступных задач нет"); return; }
        for (Ticket t : tasks) tasksContainer.addView(makeCard(t));
    }

    private View makeCard(Ticket ticket) {
        MaterialCardView card = new MaterialCardView(requireContext());
        LinearLayout.LayoutParams cp = new LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, LinearLayout.LayoutParams.WRAP_CONTENT);
        cp.setMargins(0, 0, 0, dp(14)); card.setLayoutParams(cp);
        card.setRadius(dp(22)); card.setCardElevation(dp(4)); card.setCardBackgroundColor(Color.WHITE);
        card.setContentPadding(dp(18), dp(18), dp(18), dp(18));

        LinearLayout col = new LinearLayout(requireContext()); col.setOrientation(LinearLayout.VERTICAL);

        String cat = ticket.getCategory() != null ? ticket.getCategory().getName() : "Задача";
        LinearLayout head = new LinearLayout(requireContext()); head.setOrientation(LinearLayout.HORIZONTAL); head.setGravity(android.view.Gravity.CENTER_VERTICAL);
        TextView tv = text("№" + ticket.getId() + " · " + cat, 18, "#1F2933", true);
        tv.setLayoutParams(new LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1)); head.addView(tv);
        TextView badge = text(ticket.getStatusLabel(), 12, statusColor(ticket.getStatus()), true);
        badge.setPadding(dp(10), dp(5), dp(10), dp(5)); badge.setBackgroundColor(Color.parseColor(statusBg(ticket.getStatus())));
        head.addView(badge); col.addView(head);

        String addr = ticket.getAddressText();
        String loc = (addr != null && !addr.trim().isEmpty()) ? addr.trim() : "Место не указано";
        TextView locView = text(loc, 13, "#4B5563", false);
        locView.setPadding(0, dp(8), 0, 0); col.addView(locView);

        if (ticket.getDescription() != null && !ticket.getDescription().trim().isEmpty()) {
            TextView desc = text(ticket.getDescription().trim(), 13, "#6B7280", false);
            desc.setPadding(0, dp(4), 0, 0); col.addView(desc);
        }

        TextView dateView = text("Создана: " + formatDate(ticket.getCreatedAt()), 12, "#89948D", false);
        dateView.setPadding(0, dp(6), 0, dp(8)); col.addView(dateView);

        // Action button
        if ("assigned".equals(ticket.getStatus()) || "created".equals(ticket.getStatus())) {
            MaterialButton acceptBtn = makeButton("Взяться за задачу", "#146C43");
            acceptBtn.setOnClickListener(v -> acceptTask(ticket.getId()));
            col.addView(acceptBtn);
        }

        card.addView(col);
        return card;
    }

    private void acceptTask(int ticketId) {
        apiService.residentAcceptTicket("Bearer " + token, ticketId).enqueue(new Callback<SimpleResponse>() {
            @Override
            public void onResponse(Call<SimpleResponse> c, Response<SimpleResponse> r) {
                if (!isAdded()) return;
                if (r.isSuccessful()) {
                    Toast.makeText(requireContext(), "Задача принята!", Toast.LENGTH_SHORT).show();
                    loadTasks();
                } else {
                    Toast.makeText(requireContext(), "Не удалось принять задачу", Toast.LENGTH_SHORT).show();
                }
            }
            @Override public void onFailure(Call<SimpleResponse> c, Throwable t) {
                if (isAdded()) Toast.makeText(requireContext(), "Ошибка: " + t.getMessage(), Toast.LENGTH_SHORT).show();
            }
        });
    }

    private void showEmpty(String msg) {
        if (tasksContainer == null) return;
        tasksContainer.removeAllViews();
        MaterialCardView card = new MaterialCardView(requireContext());
        card.setRadius(dp(20)); card.setCardElevation(dp(2)); card.setCardBackgroundColor(Color.WHITE); card.setContentPadding(dp(18), dp(18), dp(18), dp(18));
        card.addView(text(msg, 15, "#6B7280", false));
        tasksContainer.addView(card);
    }

    private TextView text(String s, int sp, String color, boolean bold) {
        TextView tv = new TextView(requireContext()); tv.setText(s); tv.setTextSize(sp); tv.setTextColor(Color.parseColor(color));
        if (bold) tv.setTypeface(null, android.graphics.Typeface.BOLD); return tv;
    }

    private MaterialButton makeButton(String label, String color) {
        MaterialButton b = new MaterialButton(requireContext()); b.setText(label); b.setTextSize(14); b.setAllCaps(false);
        b.setCornerRadius(dp(14)); b.setBackgroundTintList(android.content.res.ColorStateList.valueOf(Color.parseColor(color)));
        LinearLayout.LayoutParams p = new LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, dp(50));
        p.setMargins(0, dp(8), 0, 0); b.setLayoutParams(p); return b;
    }

    private static String formatDate(String iso) {
        if (iso == null || iso.isEmpty()) return "—";
        try {
            String clean = iso.replaceAll("\\.\\d+Z?$", "").replace("Z", "");
            Date d = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", Locale.getDefault()).parse(clean);
            return new SimpleDateFormat("dd.MM.yyyy HH:mm", Locale.getDefault()).format(d);
        } catch (Exception e) {
            try { return iso.substring(8,10) + "." + iso.substring(5,7) + "." + iso.substring(0,4); } catch (Exception ignored) { return iso; }
        }
    }

    private static String statusColor(String s) {
        if ("completed".equals(s)) return "#166534";
        if ("rejected".equals(s)) return "#991B1B";
        return "#1D4ED8";
    }

    private static String statusBg(String s) {
        if ("completed".equals(s)) return "#DCFCE7";
        if ("rejected".equals(s)) return "#FEE2E2";
        return "#DBEAFE";
    }

    private void hideId(View root, int id) { View v = root.findViewById(id); if (v != null) v.setVisibility(View.GONE); }
    private int dp(int v) { return (int)(v * requireContext().getResources().getDisplayMetrics().density + 0.5f); }
}
