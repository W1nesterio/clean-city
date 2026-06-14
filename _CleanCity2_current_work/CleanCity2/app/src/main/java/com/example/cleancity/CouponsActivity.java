package com.example.cleancity;

import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.View;
import android.widget.AdapterView;
import android.widget.ArrayAdapter;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.Spinner;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AlertDialog;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.content.ContextCompat;

import com.bumptech.glide.Glide;
import com.example.cleancity.api.ApiClient;
import com.example.cleancity.api.ApiService;
import com.example.cleancity.models.CityItem;
import com.example.cleancity.models.CityListResponse;
import com.example.cleancity.models.ClaimRewardResponse;
import com.example.cleancity.models.PointsResponse;
import com.example.cleancity.models.RewardItem;
import com.example.cleancity.models.RewardsResponse;
import com.example.cleancity.ui.AppUi;
import com.google.android.material.button.MaterialButton;

import java.util.ArrayList;
import java.util.List;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class CouponsActivity extends AppCompatActivity {

    private LinearLayout couponsContainer;
    private ProgressBar progressBar;
    private TextView pointsBalanceView;
    private Spinner citySpinner;

    private ApiService apiService;
    private String token;
    private final List<CityItem> cityList = new ArrayList<>();
    private ArrayAdapter<CityItem> spinnerAdapter;
    private boolean spinnerReady = false;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        AppUi.applyTheme(this);
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_coupons);

        couponsContainer = findViewById(R.id.couponsContainer);
        progressBar = findViewById(R.id.couponsProgressBar);
        pointsBalanceView = findViewById(R.id.couponsPointsBalance);
        citySpinner = findViewById(R.id.couponsCitySpinner);

        findViewById(R.id.couponsBackButton).setOnClickListener(v -> finish());

        SharedPreferences prefs = getSharedPreferences("auth", MODE_PRIVATE);
        token = prefs.getString("token", "");

        apiService = ApiClient.getClient().create(ApiService.class);

        CityItem allCities = new CityItem() {
            @Override public String toString() { return "Все города"; }
        };
        allCities.id = 0;
        allCities.name = "Все города";
        cityList.add(allCities);

        spinnerAdapter = new ArrayAdapter<>(this, android.R.layout.simple_spinner_item, cityList);
        spinnerAdapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        citySpinner.setAdapter(spinnerAdapter);

        citySpinner.setOnItemSelectedListener(new AdapterView.OnItemSelectedListener() {
            @Override
            public void onItemSelected(AdapterView<?> parent, View view, int position, long id) {
                if (!spinnerReady) return;
                CityItem selected = cityList.get(position);
                Integer cityId = selected.id == 0 ? null : selected.id;
                loadRewards(cityId);
            }
            @Override
            public void onNothingSelected(AdapterView<?> parent) {}
        });

        if (!token.isEmpty()) {
            loadPoints();
        }
        loadCities();
        loadRewards(null);
    }

    private void loadCities() {
        apiService.getCities().enqueue(new Callback<CityListResponse>() {
            @Override
            public void onResponse(Call<CityListResponse> call, Response<CityListResponse> response) {
                if (response.isSuccessful() && response.body() != null && response.body().cities != null) {
                    cityList.addAll(response.body().cities);
                    spinnerAdapter.notifyDataSetChanged();
                }
                spinnerReady = true;
            }
            @Override
            public void onFailure(Call<CityListResponse> call, Throwable t) {
                spinnerReady = true;
            }
        });
    }

    private void loadPoints() {
        apiService.getMyPoints("Bearer " + token).enqueue(new Callback<PointsResponse>() {
            @Override
            public void onResponse(Call<PointsResponse> call, Response<PointsResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    int balance = response.body().getPointsBalance();
                    pointsBalanceView.setText(balance + " баллов");
                    pointsBalanceView.setVisibility(View.VISIBLE);
                }
            }
            @Override
            public void onFailure(Call<PointsResponse> call, Throwable t) {}
        });
    }

    private void loadRewards(Integer cityId) {
        progressBar.setVisibility(View.VISIBLE);
        couponsContainer.removeAllViews();

        apiService.getRewards(cityId).enqueue(new Callback<RewardsResponse>() {
            @Override
            public void onResponse(Call<RewardsResponse> call, Response<RewardsResponse> response) {
                progressBar.setVisibility(View.GONE);
                if (response.isSuccessful() && response.body() != null) {
                    List<RewardItem> rewards = response.body().getRewards();
                    if (rewards == null || rewards.isEmpty()) {
                        showEmpty();
                    } else {
                        for (RewardItem reward : rewards) {
                            couponsContainer.addView(inflateCouponCard(reward));
                        }
                    }
                } else {
                    showEmpty();
                }
            }

            @Override
            public void onFailure(Call<RewardsResponse> call, Throwable t) {
                progressBar.setVisibility(View.GONE);
                Toast.makeText(CouponsActivity.this, "Ошибка загрузки наград", Toast.LENGTH_SHORT).show();
                showEmpty();
            }
        });
    }

    private View inflateCouponCard(RewardItem reward) {
        View card = getLayoutInflater().inflate(R.layout.item_coupon_card, couponsContainer, false);

        ImageView photoView = card.findViewById(R.id.couponPhoto);
        TextView titleView = card.findViewById(R.id.couponTitle);
        TextView pointsView = card.findViewById(R.id.couponPoints);
        TextView validityView = card.findViewById(R.id.couponValidity);
        TextView descView = card.findViewById(R.id.couponDescription);
        MaterialButton claimBtn = card.findViewById(R.id.claimButton);

        titleView.setText(reward.getTitle());
        pointsView.setText(reward.getPointsRequired() + " баллов");

        if (reward.getDescription() != null && !reward.getDescription().isEmpty()) {
            descView.setText(reward.getDescription());
        }

        String validityText = buildValidityText(reward);
        if (!validityText.isEmpty()) {
            validityView.setText(validityText);
            validityView.setVisibility(View.VISIBLE);
        }

        if (reward.getPhotoUrl() != null && !reward.getPhotoUrl().isEmpty()) {
            Glide.with(this)
                    .load(reward.getPhotoUrl())
                    .placeholder(R.drawable.bg_news_photo_rounded)
                    .centerCrop()
                    .into(photoView);

            photoView.setOnClickListener(v -> openFullscreen(reward.getPhotoUrl()));
        }

        if (token.isEmpty()) {
            claimBtn.setText("Войдите для получения");
            claimBtn.setEnabled(false);
            claimBtn.setAlpha(0.6f);
        } else {
            claimBtn.setOnClickListener(v -> claimReward(reward, claimBtn));
        }

        return card;
    }

    private void claimReward(RewardItem reward, MaterialButton button) {
        button.setEnabled(false);
        button.setText("Получение…");

        apiService.claimReward("Bearer " + token, reward.getId())
                .enqueue(new Callback<ClaimRewardResponse>() {
                    @Override
                    public void onResponse(Call<ClaimRewardResponse> call,
                                           Response<ClaimRewardResponse> response) {
                        button.setEnabled(true);
                        button.setText("Получить");

                        if (response.isSuccessful() && response.body() != null) {
                            ClaimRewardResponse result = response.body();
                            showClaimSuccess(reward.getTitle(), result.getCode(),
                                    result.getBalanceAfter());
                            pointsBalanceView.setText(result.getBalanceAfter() + " баллов");
                            pointsBalanceView.setVisibility(View.VISIBLE);
                        } else if (response.code() == 422) {
                            Toast.makeText(CouponsActivity.this,
                                    "Недостаточно баллов", Toast.LENGTH_SHORT).show();
                        } else {
                            Toast.makeText(CouponsActivity.this,
                                    "Не удалось получить награду", Toast.LENGTH_SHORT).show();
                        }
                    }

                    @Override
                    public void onFailure(Call<ClaimRewardResponse> call, Throwable t) {
                        button.setEnabled(true);
                        button.setText("Получить");
                        Toast.makeText(CouponsActivity.this,
                                "Ошибка соединения", Toast.LENGTH_SHORT).show();
                    }
                });
    }

    private void showClaimSuccess(String title, String code, int newBalance) {
        StringBuilder msg = new StringBuilder("Награда «").append(title).append("» получена!");
        if (code != null && !code.isEmpty()) {
            msg.append("\n\nВаш код: ").append(code);
        }
        msg.append("\n\nОстаток: ").append(newBalance).append(" баллов");

        new AlertDialog.Builder(this)
                .setTitle("Готово!")
                .setMessage(msg.toString())
                .setPositiveButton("OK", null)
                .show();
    }

    private void openFullscreen(String url) {
        android.content.Intent intent = new android.content.Intent(this, FullscreenPhotoActivity.class);
        intent.putExtra("photo_url", url);
        startActivity(intent);
    }

    private String buildValidityText(RewardItem reward) {
        if (reward.getValidFrom() != null && reward.getValidTo() != null) {
            return "Действует: с " + reward.getValidFrom() + " по " + reward.getValidTo();
        } else if (reward.getValidTo() != null) {
            return "Действует до: " + reward.getValidTo();
        }
        return "";
    }

    private void showEmpty() {
        TextView empty = new TextView(this);
        LinearLayout.LayoutParams lp = new LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT);
        lp.topMargin = dpToPx(40);
        empty.setLayoutParams(lp);
        empty.setText("Наград пока нет");
        empty.setTextColor(ContextCompat.getColor(this, R.color.text_gray));
        empty.setTextSize(15f);
        empty.setGravity(android.view.Gravity.CENTER);
        couponsContainer.addView(empty);
    }

    private int dpToPx(int dp) {
        float density = getResources().getDisplayMetrics().density;
        return Math.round(dp * density);
    }
}
