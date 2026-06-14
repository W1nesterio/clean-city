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

import com.example.cleancity.ui.AppUi;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.util.Locale;

public class MapPickerActivity extends AppCompatActivity {

    private WebView mapWebView;
    private TextInputEditText mapSearchEditText;
    private TextView selectedPointTextView;
    private MaterialButton searchMapButton;
    private MaterialButton confirmMapButton;
    private MaterialButton cancelMapButton;

    private Double selectedLat;
    private Double selectedLng;
    private boolean mapReady;

    private static final double DEFAULT_LAT = 53.9023;
    private static final double DEFAULT_LNG = 27.5619;

    @SuppressLint({"SetJavaScriptEnabled", "AddJavascriptInterface"})
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        AppUi.applyTheme(this);
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_map_picker);
        AppUi.applyAll(this, "Выбор места", "Выбар месца");

        mapWebView = findViewById(R.id.mapWebView);
        mapSearchEditText = findViewById(R.id.mapSearchEditText);
        selectedPointTextView = findViewById(R.id.selectedPointTextView);
        searchMapButton = findViewById(R.id.searchMapButton);
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

        mapWebView.setWebViewClient(new WebViewClient() {
            @Override
            public void onPageFinished(WebView view, String url) {
                super.onPageFinished(view, url);
                mapReady = true;
            }
        });
        mapWebView.addJavascriptInterface(new MapBridge(), "AndroidMap");
        mapWebView.loadDataWithBaseURL("https://leafletjs.com/", buildMapHtml(), "text/html", "UTF-8", null);

        updateSelectedText(selectedLat != null && selectedLng != null);

        searchMapButton.setOnClickListener(v -> searchPlace());
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

    private void searchPlace() {
        String query = mapSearchEditText.getText() != null ? mapSearchEditText.getText().toString().trim() : "";
        if (query.isEmpty()) {
            Toast.makeText(this, "Введите адрес", Toast.LENGTH_SHORT).show();
            return;
        }

        if (!mapReady) {
            Toast.makeText(this, "Карта ещё загружается", Toast.LENGTH_SHORT).show();
            return;
        }

        searchMapButton.setEnabled(false);
        selectedPointTextView.setText("Ищем место...");

        new Thread(() -> {
            try {
                String encodedQuery = URLEncoder.encode(query, "UTF-8");
                URL url = new URL("https://nominatim.openstreetmap.org/search?format=json&limit=1&addressdetails=0&q=" + encodedQuery);
                HttpURLConnection connection = (HttpURLConnection) url.openConnection();
                connection.setRequestMethod("GET");
                connection.setConnectTimeout(10000);
                connection.setReadTimeout(10000);
                connection.setRequestProperty("User-Agent", "CleanCityAndroid/1.0");

                int code = connection.getResponseCode();
                if (code < 200 || code >= 300) {
                    throw new Exception("HTTP " + code);
                }

                BufferedReader reader = new BufferedReader(new InputStreamReader(connection.getInputStream()));
                StringBuilder builder = new StringBuilder();
                String line;
                while ((line = reader.readLine()) != null) {
                    builder.append(line);
                }
                reader.close();
                connection.disconnect();

                JSONArray results = new JSONArray(builder.toString());
                if (results.length() == 0) {
                    runOnUiThread(() -> {
                        searchMapButton.setEnabled(true);
                        updateSelectedText(selectedLat != null && selectedLng != null);
                        Toast.makeText(this, "Место не найдено", Toast.LENGTH_LONG).show();
                    });
                    return;
                }

                JSONObject first = results.getJSONObject(0);
                double lat = Double.parseDouble(first.getString("lat"));
                double lng = Double.parseDouble(first.getString("lon"));

                runOnUiThread(() -> moveMapTo(lat, lng));
            } catch (Exception e) {
                runOnUiThread(() -> {
                    searchMapButton.setEnabled(true);
                    updateSelectedText(selectedLat != null && selectedLng != null);
                    Toast.makeText(this, "Поиск недоступен", Toast.LENGTH_LONG).show();
                });
            }
        }).start();
    }

    private void moveMapTo(double lat, double lng) {
        selectedLat = lat;
        selectedLng = lng;
        String script = String.format(Locale.US, "moveTo(%.7f, %.7f);", lat, lng);
        mapWebView.evaluateJavascript(script, null);
        searchMapButton.setEnabled(true);
        updateSelectedText(true);
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
                "function moveTo(lat,lng){ var point=L.latLng(lat,lng); map.setView(point,17); place(point); }" +
                "map.on('click',function(e){ place(e.latlng); });" +
                (hasMarker ? String.format(Locale.US, "place(L.latLng(%.7f,%.7f));", startLat, startLng) : "") +
                "</script></body></html>";
    }
}
