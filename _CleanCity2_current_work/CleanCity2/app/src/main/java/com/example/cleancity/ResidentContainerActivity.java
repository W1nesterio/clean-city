package com.example.cleancity;

import android.graphics.Color;
import android.os.Bundle;
import android.widget.TextView;

import androidx.appcompat.app.AppCompatActivity;
import androidx.viewpager2.widget.ViewPager2;

import com.example.cleancity.ui.AppUi;

public class ResidentContainerActivity extends AppCompatActivity {

    public static final int TAB_HOME    = 0;
    public static final int TAB_CREATE  = 1;
    public static final int TAB_HISTORY = 2;
    public static final int TAB_TASKS   = 3;
    public static final int TAB_SETTINGS= 4;

    private ViewPager2 viewPager;
    private TextView rnHome, rnCreate, rnHistory, rnTasks, rnSettings;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        AppUi.applyTheme(this);
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_resident_container);

        viewPager = findViewById(R.id.residentViewPager);
        rnHome    = findViewById(R.id.rnHome);
        rnCreate  = findViewById(R.id.rnCreate);
        rnHistory = findViewById(R.id.rnHistory);
        rnTasks   = findViewById(R.id.rnTasks);
        rnSettings= findViewById(R.id.rnSettings);

        ResidentPagerAdapter adapter = new ResidentPagerAdapter(this);
        viewPager.setAdapter(adapter);
        viewPager.setOffscreenPageLimit(4); // keep all pages in memory

        // Handle page changes (swipe) → update bottom nav highlight
        viewPager.registerOnPageChangeCallback(new ViewPager2.OnPageChangeCallback() {
            @Override
            public void onPageSelected(int position) {
                updateNavHighlight(position);
            }
        });

        // Bottom nav click → smooth switch
        rnHome.setOnClickListener(v    -> switchToTab(TAB_HOME));
        rnCreate.setOnClickListener(v  -> switchToTab(TAB_CREATE));
        rnHistory.setOnClickListener(v -> switchToTab(TAB_HISTORY));
        rnTasks.setOnClickListener(v   -> switchToTab(TAB_TASKS));
        rnSettings.setOnClickListener(v-> switchToTab(TAB_SETTINGS));

        // Start tab from intent extra (default = Home)
        int startTab = getIntent().getIntExtra("start_tab", TAB_HOME);
        viewPager.setCurrentItem(startTab, false);
        updateNavHighlight(startTab);
    }

    public void switchToTab(int index) {
        viewPager.setCurrentItem(index, true); // true = smooth animation
    }

    private void updateNavHighlight(int active) {
        TextView[] items = { rnHome, rnCreate, rnHistory, rnTasks, rnSettings };
        for (int i = 0; i < items.length; i++) {
            boolean selected = (i == active);
            items[i].setBackgroundResource(selected
                    ? R.drawable.bg_bottom_nav_selected
                    : R.drawable.bg_bottom_nav_plain);
            items[i].setTextColor(selected
                    ? getColor(R.color.white)
                    : Color.parseColor("#6B7280"));
        }
    }
}
