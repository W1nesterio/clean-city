package com.example.cleancity;

import android.graphics.Color;
import android.os.Bundle;
import android.view.View;
import android.widget.ImageView;
import android.widget.TextView;

import androidx.appcompat.app.AppCompatActivity;
import androidx.core.view.WindowCompat;

import com.bumptech.glide.Glide;
import com.example.cleancity.ui.AppUi;

public class FullscreenPhotoActivity extends AppCompatActivity {

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        AppUi.applyTheme(this);
        super.onCreate(savedInstanceState);

        WindowCompat.setDecorFitsSystemWindows(getWindow(), false);
        getWindow().setStatusBarColor(Color.BLACK);
        getWindow().setNavigationBarColor(Color.BLACK);

        setContentView(R.layout.activity_fullscreen_photo);

        String photoUrl = getIntent().getStringExtra("photo_url");

        ImageView imageView = findViewById(R.id.fullscreenImage);
        TextView closeButton = findViewById(R.id.closeButton);

        if (photoUrl != null && !photoUrl.isEmpty()) {
            Glide.with(this)
                    .load(photoUrl)
                    .placeholder(R.drawable.bg_news_photo)
                    .error(R.drawable.bg_news_photo)
                    .into(imageView);
        }

        closeButton.setOnClickListener(v -> finish());
        imageView.setOnClickListener(v -> finish());
    }

    @Override
    public void onBackPressed() {
        super.onBackPressed();
    }
}
