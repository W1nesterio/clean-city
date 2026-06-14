package com.example.cleancity;

import android.content.Intent;
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

import androidx.appcompat.app.AppCompatActivity;
import androidx.core.content.ContextCompat;

import com.bumptech.glide.Glide;
import com.example.cleancity.api.ApiClient;
import com.example.cleancity.api.ApiService;
import com.example.cleancity.models.CityItem;
import com.example.cleancity.models.CityListResponse;
import com.example.cleancity.models.NewsItem;
import com.example.cleancity.models.NewsPhoto;
import com.example.cleancity.models.NewsResponse;
import com.example.cleancity.ui.AppUi;

import java.text.SimpleDateFormat;
import java.util.ArrayList;
import java.util.Date;
import java.util.List;
import java.util.Locale;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class NewsActivity extends AppCompatActivity {

    private LinearLayout newsContainer;
    private ProgressBar progressBar;
    private Spinner citySpinner;

    private ApiService apiService;
    private final List<CityItem> cityList = new ArrayList<>();
    private ArrayAdapter<CityItem> spinnerAdapter;
    private boolean spinnerReady = false;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        AppUi.applyTheme(this);
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_news);

        newsContainer = findViewById(R.id.newsContainer);
        progressBar = findViewById(R.id.newsProgressBar);
        citySpinner = findViewById(R.id.orgSpinner);

        findViewById(R.id.newsBackButton).setOnClickListener(v -> finish());

        apiService = ApiClient.getClient().create(ApiService.class);

        CityItem allCities = new CityItem() {
            @Override public String toString() { return "Все города"; }
        };
        allCities.id = 0;
        allCities.name = "Все города";
        cityList.add(allCities);

        spinnerAdapter = new ArrayAdapter<>(this,
                android.R.layout.simple_spinner_item, cityList);
        spinnerAdapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
        citySpinner.setAdapter(spinnerAdapter);

        citySpinner.setOnItemSelectedListener(new AdapterView.OnItemSelectedListener() {
            @Override
            public void onItemSelected(AdapterView<?> parent, View view, int position, long id) {
                if (!spinnerReady) return;
                CityItem selected = cityList.get(position);
                Integer cityId = selected.id == 0 ? null : selected.id;
                loadNews(cityId);
            }
            @Override
            public void onNothingSelected(AdapterView<?> parent) {}
        });

        loadCities();
        loadNews(null);
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

    private void loadNews(Integer cityId) {
        progressBar.setVisibility(View.VISIBLE);
        newsContainer.removeAllViews();

        apiService.getNews(cityId).enqueue(new Callback<NewsResponse>() {
            @Override
            public void onResponse(Call<NewsResponse> call, Response<NewsResponse> response) {
                progressBar.setVisibility(View.GONE);
                if (response.isSuccessful() && response.body() != null) {
                    List<NewsItem> items = response.body().getNews();
                    if (items == null || items.isEmpty()) {
                        showEmpty();
                    } else {
                        for (NewsItem item : items) {
                            newsContainer.addView(inflateNewsCard(item));
                        }
                    }
                } else {
                    showEmpty();
                }
            }

            @Override
            public void onFailure(Call<NewsResponse> call, Throwable t) {
                progressBar.setVisibility(View.GONE);
                Toast.makeText(NewsActivity.this, "Ошибка загрузки новостей", Toast.LENGTH_SHORT).show();
                showEmpty();
            }
        });
    }

    private View inflateNewsCard(NewsItem item) {
        View card = getLayoutInflater().inflate(R.layout.item_news_card, newsContainer, false);

        TextView titleView = card.findViewById(R.id.newsCardTitle);
        TextView dateView = card.findViewById(R.id.newsCardDate);
        TextView bodyView = card.findViewById(R.id.newsCardBody);
        View photosScroll = card.findViewById(R.id.newsCardPhotosScroll);
        LinearLayout photosContainer = card.findViewById(R.id.newsCardPhotosContainer);

        titleView.setText(item.getTitle());
        dateView.setText(formatDate(item.getPublishedDate()));

        if (item.getBody() != null && !item.getBody().trim().isEmpty()) {
            bodyView.setText(item.getBody());
            bodyView.setVisibility(View.VISIBLE);
        } else {
            bodyView.setVisibility(View.GONE);
        }

        List<NewsPhoto> photos = item.getPhotos();
        if (photos != null && !photos.isEmpty()) {
            photosScroll.setVisibility(View.VISIBLE);
            int marginEnd = dpToPx(10);
            int w = dpToPx(210);
            int h = dpToPx(130);

            for (NewsPhoto photo : photos) {
                ImageView img = new ImageView(this);
                LinearLayout.LayoutParams lp = new LinearLayout.LayoutParams(w, h);
                lp.setMarginEnd(marginEnd);
                img.setLayoutParams(lp);
                img.setScaleType(ImageView.ScaleType.CENTER_CROP);
                img.setBackground(ContextCompat.getDrawable(this, R.drawable.bg_news_photo_rounded));
                img.setClipToOutline(true);

                Glide.with(this)
                        .load(photo.getUrl())
                        .placeholder(R.drawable.bg_news_photo_rounded)
                        .centerCrop()
                        .into(img);

                String url = photo.getUrl();
                img.setOnClickListener(v -> openFullscreen(url));

                photosContainer.addView(img);
            }
        } else {
            photosScroll.setVisibility(View.GONE);
        }

        return card;
    }

    private void openFullscreen(String url) {
        Intent intent = new Intent(this, FullscreenPhotoActivity.class);
        intent.putExtra("photo_url", url);
        startActivity(intent);
    }

    private void showEmpty() {
        TextView empty = new TextView(this);
        LinearLayout.LayoutParams lp = new LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.MATCH_PARENT,
                LinearLayout.LayoutParams.WRAP_CONTENT);
        lp.topMargin = dpToPx(40);
        empty.setLayoutParams(lp);
        empty.setText("Новостей пока нет");
        empty.setTextColor(ContextCompat.getColor(this, R.color.text_gray));
        empty.setTextSize(15f);
        empty.setGravity(android.view.Gravity.CENTER);
        newsContainer.addView(empty);
    }

    private String formatDate(String isoDate) {
        if (isoDate == null || isoDate.isEmpty()) return "";
        try {
            SimpleDateFormat inFmt = new SimpleDateFormat("yyyy-MM-dd", Locale.ROOT);
            Date d = inFmt.parse(isoDate);
            if (d == null) return isoDate;
            SimpleDateFormat outFmt = new SimpleDateFormat("d MMMM yyyy", new Locale("ru"));
            return outFmt.format(d);
        } catch (Exception e) {
            return isoDate;
        }
    }

    private int dpToPx(int dp) {
        float density = getResources().getDisplayMetrics().density;
        return Math.round(dp * density);
    }
}
