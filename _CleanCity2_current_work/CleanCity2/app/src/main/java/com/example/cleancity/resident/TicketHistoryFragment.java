package com.example.cleancity.resident;

import android.app.AlertDialog;
import android.content.Context;
import android.content.SharedPreferences;
import android.graphics.Color;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.ScrollView;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;

import com.bumptech.glide.Glide;
import com.example.cleancity.FullscreenPhotoActivity;
import com.example.cleancity.R;
import com.example.cleancity.api.ApiClient;
import com.example.cleancity.api.ApiService;
import com.example.cleancity.models.SimpleResponse;
import com.example.cleancity.models.Ticket;
import com.example.cleancity.models.TicketPhoto;
import com.example.cleancity.models.TicketsResponse;
import com.google.android.material.card.MaterialCardView;

import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.List;
import java.util.Locale;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class TicketHistoryFragment extends Fragment {

    private LinearLayout ticketsContainer;
    private ProgressBar progressBar;
    private ApiService apiService;
    private String token, serverUrl;

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        return inflater.inflate(R.layout.activity_ticket_list, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View v, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(v, savedInstanceState);

        // Hide elements not needed here
        hideId(v, R.id.bottomNavContainer);
        hideId(v, R.id.workerTabsScroll);
        hideId(v, R.id.listBackButton);
        hideId(v, R.id.openRoutePlannerButton);
        hideId(v, R.id.openTaskMapButton);

        // Update header title
        TextView title = v.findViewById(R.id.listTitleTextView);
        if (title != null) title.setText("Мои заявки");
        TextView subtitle = v.findViewById(R.id.listSubtitleTextView);
        if (subtitle != null) subtitle.setText("История обращений");

        ticketsContainer = v.findViewById(R.id.ticketsContainer);
        progressBar      = v.findViewById(R.id.listProgressBar);

        SharedPreferences prefs = requireContext().getSharedPreferences("auth", Context.MODE_PRIVATE);
        token     = prefs.getString("token", "");
        serverUrl = prefs.getString("server_url", ApiClient.DEFAULT_BASE_URL);
        apiService = ApiClient.getClient(serverUrl).create(ApiService.class);

        loadTickets();
    }

    @Override
    public void onResume() {
        super.onResume();
        loadTickets();
    }

    private void loadTickets() {
        if (token == null || token.isEmpty()) return;
        if (progressBar != null) progressBar.setVisibility(View.VISIBLE);
        apiService.getMyTickets("Bearer " + token).enqueue(new Callback<TicketsResponse>() {
            @Override
            public void onResponse(Call<TicketsResponse> c, Response<TicketsResponse> r) {
                if (!isAdded()) return;
                if (progressBar != null) progressBar.setVisibility(View.GONE);
                if (r.isSuccessful() && r.body() != null && r.body().getTickets() != null) {
                    showTickets(r.body().getTickets());
                } else {
                    showEmpty("Не удалось загрузить заявки");
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

    private void showTickets(List<Ticket> tickets) {
        if (ticketsContainer == null) return;
        ticketsContainer.removeAllViews();
        if (tickets.isEmpty()) { showEmpty("Заявок пока нет"); return; }
        for (Ticket t : tickets) ticketsContainer.addView(makeCard(t));
    }

    private View makeCard(Ticket ticket) {
        MaterialCardView card = new MaterialCardView(requireContext());
        LinearLayout.LayoutParams cp = new LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, LinearLayout.LayoutParams.WRAP_CONTENT);
        cp.setMargins(0, 0, 0, dp(14)); card.setLayoutParams(cp);
        card.setRadius(dp(22)); card.setCardElevation(dp(4)); card.setCardBackgroundColor(Color.WHITE);
        card.setContentPadding(dp(18), dp(18), dp(18), dp(18));
        card.setClickable(true); card.setFocusable(true);

        LinearLayout col = new LinearLayout(requireContext()); col.setOrientation(LinearLayout.VERTICAL);

        // Header row: title + status badge
        LinearLayout head = new LinearLayout(requireContext()); head.setOrientation(LinearLayout.HORIZONTAL); head.setGravity(android.view.Gravity.CENTER_VERTICAL);
        String cat = ticket.getCategory() != null ? ticket.getCategory().getName() : "Категория";
        TextView tvTitle = makeText("№" + ticket.getId() + " · " + cat, 18, "#1F2933", true);
        tvTitle.setLayoutParams(new LinearLayout.LayoutParams(0, LinearLayout.LayoutParams.WRAP_CONTENT, 1));
        head.addView(tvTitle);
        TextView badge = makeText(ticket.getStatusLabel(), 12, statusColor(ticket.getStatus()), true);
        badge.setPadding(dp(10), dp(5), dp(10), dp(5));
        badge.setBackgroundColor(Color.parseColor(statusBg(ticket.getStatus())));
        head.addView(badge);

        TextView deleteMenu = makeText("⋮", 22, "#4B5563", true);
        deleteMenu.setGravity(android.view.Gravity.CENTER);
        deleteMenu.setBackgroundColor(Color.parseColor("#F3F4F6"));
        LinearLayout.LayoutParams deleteParams = new LinearLayout.LayoutParams(dp(42), dp(42));
        deleteParams.setMargins(dp(8), 0, 0, 0);
        deleteMenu.setLayoutParams(deleteParams);
        deleteMenu.setOnClickListener(v -> showDeleteConfirmation(ticket));
        head.addView(deleteMenu);

        col.addView(head);

        // Location
        String addr = ticket.getAddressText();
        String loc = (addr != null && !addr.trim().isEmpty()) ? addr.trim() : "Место не указано";
        col.addView(padded(makeText(loc, 13, "#4B5563", false), 0, dp(8), 0, 0));

        // Description
        String desc = ticket.getDescription() != null && !ticket.getDescription().trim().isEmpty() ? ticket.getDescription().trim() : "Без комментария";
        col.addView(padded(makeText(desc, 13, "#6B7280", false), 0, dp(4), 0, 0));

        // Date
        col.addView(padded(makeText("Создана: " + formatDate(ticket.getCreatedAt()), 12, "#89948D", false), 0, dp(6), 0, 0));

        card.addView(col);
        card.setOnClickListener(v -> showDetail(ticket));
        return card;
    }

    private void showDetail(Ticket ticket) {
        ScrollView scroll = new ScrollView(requireContext());
        LinearLayout layout = new LinearLayout(requireContext());
        layout.setOrientation(LinearLayout.VERTICAL);
        layout.setPadding(dp(22), dp(22), dp(22), dp(22));
        scroll.addView(layout);

        // Title
        String cat = ticket.getCategory() != null ? ticket.getCategory().getName() : "Заявка";
        layout.addView(makeText("№" + ticket.getId() + " · " + cat, 20, "#1F2933", true));

        // Status badge
        TextView badge = makeText(ticket.getStatusLabel(), 13, statusColor(ticket.getStatus()), true);
        badge.setPadding(dp(10), dp(5), dp(10), dp(5));
        badge.setBackgroundColor(Color.parseColor(statusBg(ticket.getStatus())));
        LinearLayout.LayoutParams bp = new LinearLayout.LayoutParams(LinearLayout.LayoutParams.WRAP_CONTENT, LinearLayout.LayoutParams.WRAP_CONTENT);
        bp.setMargins(0, dp(10), 0, dp(14)); badge.setLayoutParams(bp); layout.addView(badge);

        // Photo
        if (ticket.getPhotos() != null && !ticket.getPhotos().isEmpty()) {
            String photoPath = null;
            for (TicketPhoto p : ticket.getPhotos()) {
                if ("before".equals(p.getType()) || p.getType() == null) { photoPath = p.getPath(); break; }
            }
            if (photoPath != null) {
                ImageView img = new ImageView(requireContext());
                LinearLayout.LayoutParams ip = new LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, dp(200));
                ip.setMargins(0, 0, 0, dp(16)); img.setLayoutParams(ip);
                img.setScaleType(ImageView.ScaleType.CENTER_CROP);
                img.setBackgroundColor(Color.parseColor("#F3F6F1"));
                String base = serverUrl.replace("/api/", "").replace("/api", "");
                String url = base + "/storage/" + photoPath;
                Glide.with(this).load(url).into(img);
                final String fUrl = url;
                img.setOnClickListener(v -> { android.content.Intent i = new android.content.Intent(requireActivity(), FullscreenPhotoActivity.class); i.putExtra("photo_url", fUrl); startActivity(i); });
                layout.addView(img);
            }
        }

        // Info rows
        String addr = ticket.getAddressText();
        addRow(layout, "Адрес", (addr != null && !addr.trim().isEmpty()) ? addr.trim() : "Место не указано");
        String desc = ticket.getDescription() != null && !ticket.getDescription().trim().isEmpty() ? ticket.getDescription().trim() : "Без комментария";
        addRow(layout, "Описание", desc);
        addRow(layout, "Создана", formatDate(ticket.getCreatedAt()));
        if (ticket.getClosedAt() != null && !ticket.getClosedAt().isEmpty())
            addRow(layout, "Закрыта", formatDate(ticket.getClosedAt()));

        new AlertDialog.Builder(requireContext()).setView(scroll).setPositiveButton("Закрыть", null).show();
    }

    private void addRow(LinearLayout parent, String label, String value) {
        LinearLayout row = new LinearLayout(requireContext()); row.setOrientation(LinearLayout.VERTICAL);
        LinearLayout.LayoutParams rp = new LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, LinearLayout.LayoutParams.WRAP_CONTENT);
        rp.setMargins(0, 0, 0, dp(12)); row.setLayoutParams(rp);

        TextView lbl = makeText(label.toUpperCase(), 11, "#6B7280", true);
        lbl.setLetterSpacing(0.08f); row.addView(lbl);

        TextView val = makeText(value, 14, "#1F2933", false);
        val.setPadding(0, dp(2), 0, 0); row.addView(val);

        // Thin divider
        View div = new View(requireContext());
        div.setLayoutParams(new LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, dp(1)));
        div.setBackgroundColor(Color.parseColor("#F0F4EE"));
        row.addView(div);

        parent.addView(row);
    }

    private void showDeleteConfirmation(Ticket ticket) {
        AlertDialog dialog = new AlertDialog.Builder(requireContext())
                .setTitle("Удалить заявку из истории?")
                .setMessage("Заявка исчезнет из вашего списка. Это действие нельзя отменить.")
                .setNegativeButton("Отмена", null)
                .setPositiveButton("Удалить", (d, which) -> deleteTicket(ticket.getId()))
                .create();
        dialog.setOnShowListener(d -> dialog.getButton(AlertDialog.BUTTON_POSITIVE).setTextColor(Color.parseColor("#B91C1C")));
        dialog.show();
    }

    private void deleteTicket(int ticketId) {
        if (token == null || token.trim().isEmpty()) {
            Toast.makeText(requireContext(), "Авторизация не найдена. Войдите заново", Toast.LENGTH_LONG).show();
            return;
        }

        if (progressBar != null) progressBar.setVisibility(View.VISIBLE);
        apiService.deleteTicket("Bearer " + token, ticketId).enqueue(new Callback<SimpleResponse>() {
            @Override
            public void onResponse(Call<SimpleResponse> call, Response<SimpleResponse> response) {
                if (!isAdded()) return;
                if (progressBar != null) progressBar.setVisibility(View.GONE);
                if (response.isSuccessful()) {
                    Toast.makeText(requireContext(), "Заявка удалена из истории", Toast.LENGTH_LONG).show();
                    loadTickets();
                } else if (response.code() == 404 || response.code() == 405) {
                    Toast.makeText(requireContext(), "Сервер не поддерживает удаление заявок", Toast.LENGTH_LONG).show();
                } else {
                    Toast.makeText(requireContext(), "Не удалось удалить заявку", Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onFailure(Call<SimpleResponse> call, Throwable t) {
                if (!isAdded()) return;
                if (progressBar != null) progressBar.setVisibility(View.GONE);
                Toast.makeText(requireContext(), "Ошибка подключения", Toast.LENGTH_LONG).show();
            }
        });
    }

    private void showEmpty(String msg) {
        if (ticketsContainer == null) return;
        ticketsContainer.removeAllViews();
        MaterialCardView card = new MaterialCardView(requireContext());
        card.setRadius(dp(20)); card.setCardElevation(dp(2)); card.setCardBackgroundColor(Color.WHITE); card.setContentPadding(dp(18), dp(18), dp(18), dp(18));
        card.addView(makeText(msg, 15, "#6B7280", false));
        ticketsContainer.addView(card);
    }

    private TextView makeText(String text, int sp, String color, boolean bold) {
        TextView tv = new TextView(requireContext()); tv.setText(text); tv.setTextSize(sp); tv.setTextColor(Color.parseColor(color));
        if (bold) tv.setTypeface(null, android.graphics.Typeface.BOLD);
        return tv;
    }

    private View padded(View v, int l, int t, int r, int b) {
        LinearLayout.LayoutParams p = new LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, LinearLayout.LayoutParams.WRAP_CONTENT);
        p.setMargins(l, t, r, b); v.setLayoutParams(p); return v;
    }

    private static String formatDate(String iso) {
        if (iso == null || iso.isEmpty()) return "—";
        try {
            String clean = iso.replaceAll("\\.\\d+Z?$", "").replace("Z", "");
            SimpleDateFormat inF = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss", Locale.getDefault());
            Date d = inF.parse(clean);
            return new SimpleDateFormat("dd.MM.yyyy HH:mm", Locale.getDefault()).format(d);
        } catch (Exception e) {
            try { return iso.substring(8,10) + "." + iso.substring(5,7) + "." + iso.substring(0,4); } catch (Exception ignored) { return iso; }
        }
    }

    private static String statusColor(String s) {
        if (s == null) return "#374151";
        switch (s) {
            case "completed": return "#166534";
            case "rejected":  return "#991B1B";
            case "in_progress": case "accepted": case "assigned": return "#1D4ED8";
            default: return "#374151";
        }
    }

    private static String statusBg(String s) {
        if (s == null) return "#F3F4F6";
        switch (s) {
            case "completed": return "#DCFCE7";
            case "rejected":  return "#FEE2E2";
            case "in_progress": case "accepted": return "#DBEAFE";
            case "assigned":  return "#FEF3C7";
            default: return "#F3F4F6";
        }
    }

    private void hideId(View root, int id) { View v = root.findViewById(id); if (v != null) v.setVisibility(View.GONE); }
    private int dp(int v) { return (int)(v * requireContext().getResources().getDisplayMetrics().density + 0.5f); }
}
