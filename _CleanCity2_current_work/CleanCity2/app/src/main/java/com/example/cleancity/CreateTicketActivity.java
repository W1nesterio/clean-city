package com.example.cleancity;

import android.Manifest;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.content.res.ColorStateList;
import android.database.Cursor;
import android.location.Location;
import android.location.LocationListener;
import android.location.LocationManager;
import android.net.Uri;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.provider.OpenableColumns;
import android.view.View;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;

import com.example.cleancity.api.ApiClient;
import com.example.cleancity.api.ApiService;
import com.example.cleancity.models.CategoriesResponse;
import com.example.cleancity.models.Category;
import com.example.cleancity.models.CreateTicketResponse;
import com.example.cleancity.ui.AppUi;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.util.ArrayList;
import java.util.List;
import java.util.Locale;

import okhttp3.MediaType;
import okhttp3.MultipartBody;
import okhttp3.RequestBody;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class CreateTicketActivity extends AppCompatActivity {

    private static final int PICK_IMAGE_REQUEST = 1001;
    private static final int MAP_PICK_REQUEST = 1002;
    private static final int LOCATION_PERMISSION_REQUEST = 2001;

    private LinearLayout categoryButtonsContainer;
    private TextInputEditText descriptionEditText;
    private TextView locationStatusTextView;
    private TextView photoNameTextView;
    private ImageView photoPreviewImageView;
    private MaterialButton refreshLocationButton;
    private MaterialButton pickMapButton;
    private MaterialButton selectPhotoButton;
    private MaterialButton sendTicketButton;
    private MaterialButton createBackButton;
    private ProgressBar progressBar;

    private ApiService apiService;
    private final List<Category> categories = new ArrayList<>();
    private final List<MaterialButton> categoryButtons = new ArrayList<>();
    private Uri selectedPhotoUri;
    private Double currentLat;
    private Double currentLng;
    private Category selectedCategory;
    private boolean openMapAfterPermission;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        AppUi.applyTheme(this);
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_create_ticket);
        AppUi.applyAll(this, "Создание заявки", "Стварэнне заяўкі");

        categoryButtonsContainer = findViewById(R.id.categoryButtonsContainer);
        descriptionEditText = findViewById(R.id.descriptionEditText);
        locationStatusTextView = findViewById(R.id.locationStatusTextView);
        photoNameTextView = findViewById(R.id.photoNameTextView);
        photoPreviewImageView = findViewById(R.id.photoPreviewImageView);
        refreshLocationButton = findViewById(R.id.refreshLocationButton);
        pickMapButton = findViewById(R.id.pickMapButton);
        selectPhotoButton = findViewById(R.id.selectPhotoButton);
        sendTicketButton = findViewById(R.id.sendTicketButton);
        createBackButton = findViewById(R.id.createBackButton);
        progressBar = findViewById(R.id.createProgressBar);

        String serverUrl = getSharedPreferences("auth", MODE_PRIVATE).getString("server_url", ApiClient.DEFAULT_BASE_URL);
        apiService = ApiClient.getClient(serverUrl).create(ApiService.class);

        createBackButton.setOnClickListener(v -> finish());
        selectPhotoButton.setOnClickListener(v -> openImagePicker());
        refreshLocationButton.setOnClickListener(v -> requestLocationPermissionIfNeeded());
        pickMapButton.setOnClickListener(v -> openMapWithCurrentLocation());
        sendTicketButton.setOnClickListener(v -> createTicket());

        createBackButton.setContentDescription("Назад");
        selectPhotoButton.setContentDescription("Выбрать фото для заявки");
        refreshLocationButton.setContentDescription("Определить местоположение");
        pickMapButton.setContentDescription("Выбрать место на карте");

        loadCategories();
        locationStatusTextView.setText("Не выбрано");
        requestLocationPermissionIfNeeded();
    }

    private void loadCategories() {
        setLoading(true);
        apiService.getCategories().enqueue(new Callback<CategoriesResponse>() {
            @Override
            public void onResponse(Call<CategoriesResponse> call, Response<CategoriesResponse> response) {
                setLoading(false);
                if (response.isSuccessful() && response.body() != null && response.body().getCategories() != null) {
                    categories.clear();
                    categories.addAll(response.body().getCategories());
                    buildCategoryButtons();
                } else {
                    Toast.makeText(CreateTicketActivity.this, "Категории не загружены", Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onFailure(Call<CategoriesResponse> call, Throwable t) {
                setLoading(false);
                Toast.makeText(CreateTicketActivity.this, "Ошибка подключения: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    private void buildCategoryButtons() {
        categoryButtonsContainer.removeAllViews();
        categoryButtons.clear();
        selectedCategory = categories.isEmpty() ? null : categories.get(0);

        for (Category category : categories) {
            MaterialButton button = new MaterialButton(this);
            button.setText(category.getName());
            button.setAllCaps(false);
            button.setTextSize(16);
            button.setCornerRadius(dp(16));
            button.setMinHeight(dp(54));
            button.setContentDescription("Категория " + category.getName());
            button.setInsetTop(0);
            button.setInsetBottom(0);
            button.setStrokeWidth(dp(1));
            button.setTag(category);

            LinearLayout.LayoutParams params = new LinearLayout.LayoutParams(
                    LinearLayout.LayoutParams.MATCH_PARENT,
                    dp(54)
            );
            params.setMargins(0, 0, 0, dp(8));
            categoryButtonsContainer.addView(button, params);
            categoryButtons.add(button);

            button.setOnClickListener(v -> {
                selectedCategory = (Category) v.getTag();
                updateCategoryButtons();
            });
        }

        updateCategoryButtons();
        AppUi.apply(this);
    }

    private void updateCategoryButtons() {
        int green = ContextCompat.getColor(this, R.color.green_main);
        int white = ContextCompat.getColor(this, R.color.white);
        int textDark = ContextCompat.getColor(this, R.color.text_dark);
        int line = ContextCompat.getColor(this, R.color.line_light);

        for (MaterialButton button : categoryButtons) {
            Category category = (Category) button.getTag();
            boolean selected = selectedCategory != null && category.getId() == selectedCategory.getId();

            button.setBackgroundTintList(ColorStateList.valueOf(selected ? green : white));
            button.setTextColor(selected ? white : textDark);
            button.setStrokeColor(ColorStateList.valueOf(selected ? green : line));
        }
    }

    private void requestLocationPermissionIfNeeded() {
        boolean fineGranted = ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED;
        boolean coarseGranted = ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_COARSE_LOCATION) == PackageManager.PERMISSION_GRANTED;

        if (fineGranted || coarseGranted) {
            detectLocation();
            return;
        }

        ActivityCompat.requestPermissions(
                this,
                new String[]{Manifest.permission.ACCESS_FINE_LOCATION, Manifest.permission.ACCESS_COARSE_LOCATION},
                LOCATION_PERMISSION_REQUEST
        );
    }

    @Override
    public void onRequestPermissionsResult(int requestCode, @NonNull String[] permissions, @NonNull int[] grantResults) {
        super.onRequestPermissionsResult(requestCode, permissions, grantResults);

        if (requestCode == LOCATION_PERMISSION_REQUEST) {
            boolean granted = false;
            for (int result : grantResults) {
                if (result == PackageManager.PERMISSION_GRANTED) {
                    granted = true;
                    break;
                }
            }

            if (granted) {
                if (openMapAfterPermission) {
                    openMapAfterPermission = false;
                    detectLocationAndOpenMap();
                } else {
                    detectLocation();
                }
            } else {
                currentLat = null;
                currentLng = null;
                openMapAfterPermission = false;
                locationStatusTextView.setText("Выберите на карте");
                Toast.makeText(this, "Выберите место на карте", Toast.LENGTH_LONG).show();
                openMapPicker();
            }
        }
    }

    private void detectLocation() {
        LocationManager locationManager = (LocationManager) getSystemService(LOCATION_SERVICE);
        if (locationManager == null) {
            locationStatusTextView.setText("Выберите на карте");
            return;
        }

        boolean fineGranted = ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED;
        boolean coarseGranted = ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_COARSE_LOCATION) == PackageManager.PERMISSION_GRANTED;
        if (!fineGranted && !coarseGranted) {
            return;
        }

        locationStatusTextView.setText("Определяем...");

        try {
            Location lastLocation = null;

            if (locationManager.isProviderEnabled(LocationManager.NETWORK_PROVIDER)) {
                lastLocation = locationManager.getLastKnownLocation(LocationManager.NETWORK_PROVIDER);
            }

            if (lastLocation == null && locationManager.isProviderEnabled(LocationManager.GPS_PROVIDER)) {
                lastLocation = locationManager.getLastKnownLocation(LocationManager.GPS_PROVIDER);
            }

            if (lastLocation != null) {
                setCurrentLocation(lastLocation.getLatitude(), lastLocation.getLongitude(), "Место определено");
            }

            String provider = null;
            if (locationManager.isProviderEnabled(LocationManager.NETWORK_PROVIDER)) {
                provider = LocationManager.NETWORK_PROVIDER;
            } else if (locationManager.isProviderEnabled(LocationManager.GPS_PROVIDER)) {
                provider = LocationManager.GPS_PROVIDER;
            }

            if (provider == null) {
                locationStatusTextView.setText("Выберите на карте");
                return;
            }

            LocationListener listener = new LocationListener() {
                @Override
                public void onLocationChanged(@NonNull Location location) {
                    setCurrentLocation(location.getLatitude(), location.getLongitude(), "Место определено");
                    try {
                        locationManager.removeUpdates(this);
                    } catch (SecurityException ignored) {
                    }
                }
            };

            locationManager.requestSingleUpdate(provider, listener, Looper.getMainLooper());
        } catch (SecurityException e) {
            locationStatusTextView.setText("Выберите на карте");
        }
    }

    private void setCurrentLocation(double lat, double lng, String sourceText) {
        currentLat = lat;
        currentLng = lng;
        locationStatusTextView.setText(sourceText);
    }

    private void openMapWithCurrentLocation() {
        boolean fineGranted = ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED;
        boolean coarseGranted = ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_COARSE_LOCATION) == PackageManager.PERMISSION_GRANTED;

        if (fineGranted || coarseGranted) {
            detectLocationAndOpenMap();
            return;
        }

        openMapAfterPermission = true;
        ActivityCompat.requestPermissions(
                this,
                new String[]{Manifest.permission.ACCESS_FINE_LOCATION, Manifest.permission.ACCESS_COARSE_LOCATION},
                LOCATION_PERMISSION_REQUEST
        );
    }

    private void detectLocationAndOpenMap() {
        LocationManager locationManager = (LocationManager) getSystemService(LOCATION_SERVICE);
        if (locationManager == null) {
            openMapPicker();
            return;
        }

        boolean fineGranted = ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED;
        boolean coarseGranted = ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_COARSE_LOCATION) == PackageManager.PERMISSION_GRANTED;
        if (!fineGranted && !coarseGranted) {
            openMapPicker();
            return;
        }

        locationStatusTextView.setText("Определяем...");

        try {
            Location lastLocation = getBestLastLocation(locationManager);
            if (lastLocation != null) {
                setCurrentLocation(lastLocation.getLatitude(), lastLocation.getLongitude(), "Место определено");
                openMapPicker();
                return;
            }

            String provider = getBestEnabledProvider(locationManager);
            if (provider == null) {
                locationStatusTextView.setText("Выберите на карте");
                openMapPicker();
                return;
            }

            Handler handler = new Handler(Looper.getMainLooper());
            final boolean[] opened = {false};
            Runnable fallback = () -> {
                if (!opened[0]) {
                    opened[0] = true;
                    locationStatusTextView.setText("Выберите на карте");
                    openMapPicker();
                }
            };
            handler.postDelayed(fallback, 3500);

            LocationListener listener = new LocationListener() {
                @Override
                public void onLocationChanged(@NonNull Location location) {
                    if (!opened[0]) {
                        opened[0] = true;
                        handler.removeCallbacks(fallback);
                        setCurrentLocation(location.getLatitude(), location.getLongitude(), "Место определено");
                        openMapPicker();
                    }

                    try {
                        locationManager.removeUpdates(this);
                    } catch (SecurityException ignored) {
                    }
                }
            };

            locationManager.requestSingleUpdate(provider, listener, Looper.getMainLooper());
        } catch (SecurityException e) {
            locationStatusTextView.setText("Выберите на карте");
            openMapPicker();
        }
    }

    private Location getBestLastLocation(LocationManager locationManager) throws SecurityException {
        Location lastLocation = null;

        if (locationManager.isProviderEnabled(LocationManager.NETWORK_PROVIDER)) {
            lastLocation = locationManager.getLastKnownLocation(LocationManager.NETWORK_PROVIDER);
        }

        if (lastLocation == null && locationManager.isProviderEnabled(LocationManager.GPS_PROVIDER)) {
            lastLocation = locationManager.getLastKnownLocation(LocationManager.GPS_PROVIDER);
        }

        return lastLocation;
    }

    private String getBestEnabledProvider(LocationManager locationManager) {
        if (locationManager.isProviderEnabled(LocationManager.NETWORK_PROVIDER)) {
            return LocationManager.NETWORK_PROVIDER;
        }

        if (locationManager.isProviderEnabled(LocationManager.GPS_PROVIDER)) {
            return LocationManager.GPS_PROVIDER;
        }

        return null;
    }

    private void openMapPicker() {
        Intent intent = new Intent(this, MapPickerActivity.class);
        if (currentLat != null && currentLng != null) {
            intent.putExtra("lat", currentLat);
            intent.putExtra("lng", currentLng);
        }
        startActivityForResult(intent, MAP_PICK_REQUEST);
    }

    private void openImagePicker() {
        Intent intent = new Intent(Intent.ACTION_GET_CONTENT);
        intent.setType("image/*");
        startActivityForResult(Intent.createChooser(intent, "Выберите фото"), PICK_IMAGE_REQUEST);
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, @Nullable Intent data) {
        super.onActivityResult(requestCode, resultCode, data);

        if (requestCode == PICK_IMAGE_REQUEST && resultCode == RESULT_OK && data != null && data.getData() != null) {
            selectedPhotoUri = data.getData();
            photoNameTextView.setText(getFileName(selectedPhotoUri));
            photoPreviewImageView.setImageURI(selectedPhotoUri);
            photoPreviewImageView.setVisibility(View.VISIBLE);
            return;
        }

        if (requestCode == MAP_PICK_REQUEST && resultCode == RESULT_OK && data != null) {
            double lat = data.getDoubleExtra("lat", 0);
            double lng = data.getDoubleExtra("lng", 0);
            if (lat != 0 || lng != 0) {
                setCurrentLocation(lat, lng, "Точка выбрана");
            }
        }
    }

    private void createTicket() {
        if (selectedCategory == null) {
            Toast.makeText(this, "Выберите категорию", Toast.LENGTH_SHORT).show();
            return;
        }

        if (selectedPhotoUri == null) {
            Toast.makeText(this, "Выберите фото", Toast.LENGTH_SHORT).show();
            return;
        }

        if (currentLat == null || currentLng == null) {
            Toast.makeText(this, "Выберите место", Toast.LENGTH_LONG).show();
            return;
        }

        String description = buildDescriptionForSubmit();
        String token = getSharedPreferences("auth", MODE_PRIVATE).getString("token", "");

        try {
            File file = createTempFileFromUri(selectedPhotoUri);
            String mimeType = getContentResolver().getType(selectedPhotoUri);
            if (mimeType == null) {
                mimeType = "image/*";
            }

            RequestBody photoBody = RequestBody.create(MediaType.parse(mimeType), file);
            MultipartBody.Part photoPart = MultipartBody.Part.createFormData("photo_before", file.getName(), photoBody);

            RequestBody categoryIdBody = textPart(String.valueOf(selectedCategory.getId()));
            RequestBody latBody = textPart(String.format(Locale.US, "%.7f", currentLat));
            RequestBody lngBody = textPart(String.format(Locale.US, "%.7f", currentLng));
            RequestBody descriptionBody = textPart(description);
            RequestBody priorityBody = textPart("normal");
            setLoading(true);

            apiService.createTicket(
                    "Bearer " + token,
                    categoryIdBody,
                    latBody,
                    lngBody,
                    descriptionBody,
                    priorityBody,
                    photoPart
            ).enqueue(new Callback<CreateTicketResponse>() {
                @Override
                public void onResponse(Call<CreateTicketResponse> call, Response<CreateTicketResponse> response) {
                    setLoading(false);
                    if (response.isSuccessful() && response.body() != null) {
                        Toast.makeText(CreateTicketActivity.this, response.body().getMessage(), Toast.LENGTH_LONG).show();
                        finish();
                    } else {
                        Toast.makeText(CreateTicketActivity.this, "Заявка не отправлена", Toast.LENGTH_LONG).show();
                    }
                }

                @Override
                public void onFailure(Call<CreateTicketResponse> call, Throwable t) {
                    setLoading(false);
                    Toast.makeText(CreateTicketActivity.this, "Ошибка подключения: " + t.getMessage(), Toast.LENGTH_LONG).show();
                }
            });
        } catch (IOException e) {
            Toast.makeText(this, "Не удалось прочитать фото", Toast.LENGTH_LONG).show();
        }
    }

    private String buildDescriptionForSubmit() {
        String description = getText(descriptionEditText);
        return description.length() > 200 ? description.substring(0, 200) : description;
    }

    private RequestBody textPart(String value) {
        return RequestBody.create(MediaType.parse("text/plain"), value == null ? "" : value);
    }

    private String getText(TextInputEditText editText) {
        return editText.getText() != null ? editText.getText().toString().trim() : "";
    }

    private void setLoading(boolean loading) {
        progressBar.setVisibility(loading ? View.VISIBLE : View.GONE);
        sendTicketButton.setEnabled(!loading);
        selectPhotoButton.setEnabled(!loading);
        refreshLocationButton.setEnabled(!loading);
        pickMapButton.setEnabled(!loading);
        for (MaterialButton categoryButton : categoryButtons) {
            categoryButton.setEnabled(!loading);
        }
    }

    private File createTempFileFromUri(Uri uri) throws IOException {
        InputStream inputStream = getContentResolver().openInputStream(uri);
        File file = new File(getCacheDir(), "ticket_photo_" + System.currentTimeMillis() + ".jpg");
        FileOutputStream outputStream = new FileOutputStream(file);

        byte[] buffer = new byte[4096];
        int read;
        while (inputStream != null && (read = inputStream.read(buffer)) != -1) {
            outputStream.write(buffer, 0, read);
        }

        outputStream.flush();
        outputStream.close();
        if (inputStream != null) {
            inputStream.close();
        }

        return file;
    }

    private String getFileName(Uri uri) {
        String result = "фото выбрано";
        Cursor cursor = getContentResolver().query(uri, null, null, null, null);

        if (cursor != null) {
            try {
                int nameIndex = cursor.getColumnIndex(OpenableColumns.DISPLAY_NAME);
                if (nameIndex >= 0 && cursor.moveToFirst()) {
                    result = cursor.getString(nameIndex);
                }
            } finally {
                cursor.close();
            }
        }

        return result;
    }

    private int dp(int value) {
        return (int) (value * getResources().getDisplayMetrics().density + 0.5f);
    }
}
