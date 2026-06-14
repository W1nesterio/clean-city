package com.example.cleancity.resident;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;

import com.example.cleancity.CouponsActivity;
import com.example.cleancity.NewsActivity;
import com.example.cleancity.R;
import com.example.cleancity.ResidentContainerActivity;
import com.example.cleancity.api.ApiClient;
import com.example.cleancity.api.ApiService;
import com.example.cleancity.models.PointsResponse;
import com.example.cleancity.models.Ticket;
import com.example.cleancity.models.TicketsResponse;
import com.google.android.material.button.MaterialButton;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class HomeResidentFragment extends Fragment {

    private TextView roleView, titleView, subtitleView, pointsView, nextStepView;
    private MaterialButton logoutButton, rewardButton, newsButton;

    private ApiService apiService;
    private String token;

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater,
                             @Nullable ViewGroup container,
                             @Nullable Bundle savedInstanceState) {
        return inflater.inflate(R.layout.activity_home, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View v, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(v, savedInstanceState);

        // Hide views not needed for resident home tab
        hide(v, R.id.bottomNavContainer);
        hide(v, R.id.primaryCard);
        hide(v, R.id.secondaryCard);
        hide(v, R.id.tertiaryCard);
        hide(v, R.id.homeSectionTitleTextView);
        hide(v, R.id.profileAvatarContainer);
        hide(v, R.id.summaryCard);
        show(v, R.id.workerDashboardContainer);
        show(v, R.id.pointsCard);
        show(v, R.id.logoutButton);

        roleView     = v.findViewById(R.id.homeRoleTextView);
        titleView    = v.findViewById(R.id.homeTitleTextView);
        subtitleView = v.findViewById(R.id.homeSubtitleTextView);
        pointsView   = v.findViewById(R.id.pointsCountTextView);
        nextStepView = v.findViewById(R.id.homeNextStepTextView);
        logoutButton = v.findViewById(R.id.logoutButton);
        rewardButton = v.findViewById(R.id.rewardButton);
        newsButton   = v.findViewById(R.id.newsButton);

        roleView.setText("Житель");
        titleView.setText("Чистый город");
        subtitleView.setText("Обращения и баллы");

        SharedPreferences prefs = requireContext().getSharedPreferences("auth", android.content.Context.MODE_PRIVATE);
        token = prefs.getString("token", "");
        String serverUrl = prefs.getString("server_url", ApiClient.DEFAULT_BASE_URL);
        apiService = ApiClient.getClient(serverUrl).create(ApiService.class);

        logoutButton.setOnClickListener(lv -> logout(prefs));
        rewardButton.setOnClickListener(lv -> startActivity(new Intent(requireActivity(), CouponsActivity.class)));
        newsButton.setOnClickListener(lv -> startActivity(new Intent(requireActivity(), NewsActivity.class)));

        loadData();
    }

    @Override
    public void onResume() {
        super.onResume();
        loadData();
    }

    private void loadData() {
        if (token == null || token.isEmpty()) return;

        apiService.getMyTickets("Bearer " + token).enqueue(new Callback<TicketsResponse>() {
            @Override
            public void onResponse(Call<TicketsResponse> call, Response<TicketsResponse> response) {
                if (!isAdded() || response.body() == null) return;
                int active = 0, work = 0, done = 0;
                for (Ticket t : response.body().getTickets()) {
                    String s = t.getStatus();
                    if ("completed".equals(s)) done++;
                    else if ("in_progress".equals(s) || "accepted".equals(s) || "assigned".equals(s)) work++;
                    else if (!"rejected".equals(s) && !"duplicate".equals(s)) active++;
                }
                if (nextStepView != null) {
                    nextStepView.setText("Новых: " + active + " · В работе: " + work + " · Выполнено: " + done);
                }
            }
            @Override public void onFailure(Call<TicketsResponse> call, Throwable t) {}
        });

        apiService.getMyPoints("Bearer " + token).enqueue(new Callback<PointsResponse>() {
            @Override
            public void onResponse(Call<PointsResponse> call, Response<PointsResponse> response) {
                if (!isAdded() || response.body() == null || pointsView == null) return;
                pointsView.setText(String.valueOf(response.body().getPointsBalance()));
            }
            @Override public void onFailure(Call<PointsResponse> call, Throwable t) {}
        });
    }

    private void logout(SharedPreferences prefs) {
        prefs.edit().clear().apply();
        Intent intent = new Intent(requireActivity(), com.example.cleancity.MainActivity.class);
        intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
        startActivity(intent);
    }

    private static void hide(View root, int id) {
        View v = root.findViewById(id);
        if (v != null) v.setVisibility(View.GONE);
    }

    private static void show(View root, int id) {
        View v = root.findViewById(id);
        if (v != null) v.setVisibility(View.VISIBLE);
    }
}
