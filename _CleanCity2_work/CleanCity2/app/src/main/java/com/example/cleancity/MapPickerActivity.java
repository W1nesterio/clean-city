package com.example.cleancity;

import android.annotation.SuppressLint;
import android.content.Intent;
import android.os.Bundle;
import android.webkit.JavascriptInterface;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.google.android.material.button.MaterialButton;

import java.util.Locale;

public class MapPickerActivity extends AppCompatActivity {

    private WebView mapWebView;
    private TextView selectedPointTextView;
    private MaterialButton confirmMapButton;
    private MaterialButton cancelMapButton;

    private Double selectedLat;
    private Double selectedLng;

    private static final double DEFAULT_LAT = 53.9023;
    private static final double DEFAULT_LNG = 27.5619;

    @SuppressLint({"SetJavaScriptEnabled", "AddJavascriptInterface"})
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_map_picker);

        mapWebView = findViewById(R.id.mapWebView);
        selectedPointTextView = findViewById(R.id.selectedPointTextView);
        confirmMapButton = findViewById(R.id.confirmMapButton);
        cancelMapButton = findViewById(R.id.cancelMapButton);

        selectedLat = getIntent().hasExtra("lat") ? getIntent().getDoubleExtra("lat", DEFAULT_LAT) : null;
        selectedLng = getIntent().hasExtra("lng") ? getIntent().getDoubleExtra("lng", DEFAULT_LNG) : null;

        WebSettings settings = mapWebView.getSettings();
        settings.setJavaScriptEnabled(true);
        settings.setDomStorageEnabled(true);
        settings.setLoadWithOverviewMode(true);
        settings.setUseWideViewPort(true);
        settings.setBuiltInZoomControls(false);
        settings.setDisplayZoomControls(false);

        mapWebView.setWebViewClient(new WebViewClient());
        mapWebView.addJavascriptInterface(new MapBridge(), "AndroidMap");
        mapWebView.loadDataWithBaseURL("https://leafletjs.com/", buildMapHtml(), "text/html", "UTF-8", null);

        updateSelectedText(selectedLat != null && selectedLng != null);

        cancelMapButton.setOnClickListener(v -> finish());
        confirmMapButton.setOnClickListener(v -> confirmPoint());
    }

    private void confirmPoint() {
        if (selectedLat == null || selectedLng == null) {
            Toast.makeText(this, "Отметьте место на карте", Toast.LENGTH_SHORT).show();
            return;
        }

        Intent result = new Intent();
        result.putExtra("lat", selectedLat);
        result.putExtra("lng", selectedLng);
        setResult(RESULT_OK, result);
        finish();
    }

    private void updateSelectedText(boolean selected) {
        if (selected) {
            selectedPointTextView.setText("Место выбрано");
        } else {
            selectedPointTextView.setText("Коснитесь карты, чтобы поставить метку");
        }
    }

    public class MapBridge {
        @JavascriptInterface
        public void onPointSelected(double lat, double lng) {
            runOnUiThread(() -> {
                selectedLat = lat;
                selectedLng = lng;
                updateSelectedText(true);
            });
        }
    }

    private String buildMapHtml() {
        double startLat = selectedLat != null ? selectedLat : DEFAULT_LAT;
        double startLng = selectedLng != null ? selectedLng : DEFAULT_LNG;
        boolean hasMarker = selectedLat != null && selectedLng != null;

        return "<!DOCTYPE html>" +
                "<html><head>" +
                "<meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'/>" +
                "<link rel='stylesheet' href='https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'/>" +
                "<script src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'></script>" +
                "<style>html,body,#map{height:100%;margin:0;padding:0;}" +
                ".leaflet-control-attribution{font-size:10px;}" +
                "</style></head><body>" +
                "<div id='map'></div>" +
                "<script>" +
                String.format(Locale.US, "var map=L.map('map',{zoomControl:true}).setView([%.7f,%.7f],15);", startLat, startLng) +
                "L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:19,attribution:'© OpenStreetMap'}).addTo(map);" +
                "var marker=null;" +
                "function send(lat,lng){ if(window.AndroidMap){ AndroidMap.onPointSelected(lat,lng); } }" +
                "function place(latlng){" +
                " if(marker===null){ marker=L.marker(latlng,{draggable:true}).addTo(map); marker.on('dragend',function(e){var p=e.target.getLatLng(); send(p.lat,p.lng);}); }" +
                " else { marker.setLatLng(latlng); }" +
                " send(latlng.lat,latlng.lng);" +
                "}" +
                "map.on('click',function(e){ place(e.latlng); });" +
                (hasMarker ? String.format(Locale.US, "place(L.latLng(%.7f,%.7f));", startLat, startLng) : "") +
                "</script></body></html>";
    }
}
