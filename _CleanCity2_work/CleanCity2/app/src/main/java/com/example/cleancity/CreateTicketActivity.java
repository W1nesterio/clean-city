package com.example.cleancity;

import android.Manifest;
import android.content.Intent;
import android.content.pm.PackageManager;
import android.location.Location;
import android.location.LocationListener;
import android.location.LocationManager;
import android.net.Uri;
import android.os.Bundle;
import android.os.Looper;
import android.view.View;
import android.widget.ArrayAdapter;
import android.widget.ImageView;
import android.widget.ProgressBar;
import android.widget.Spinner;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.activity.result.ActivityResultLauncher;
import androidx.activity.result.contract.ActivityResultContracts;
import androidx.appcompat.app.AppCompatActivity;
import androidx.core.app.ActivityCompat;
import androidx.core.content.ContextCompat;
import androidx.core.content.FileProvider;

import com.example.cleancity.api.ApiClient;
import com.example.cleancity.api.ApiService;
import com.example.cleancity.models.CategoriesResponse;
import com.example.cleancity.models.Category;
import com.example.cleancity.models.CreateTicketResponse;
import com.google.android.material.dialog.MaterialAlertDialogBuilder;
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

    private static final int MAP_PICK_REQUEST = 1002;
    private static final int LOCATION_PERMISSION_REQUEST = 2001;

    private Spinner categorySpinner;
    private TextInputEditText descriptionEditText;
    private TextView locationStatusTextView;
    private TextView photoNameTextView;
    private ImageView photoPreviewImageView;
    private MaterialButton refreshLocationButton;
    private MaterialButton pickMapButton;
    private MaterialButton selectPhotoButton;
    private MaterialButton sendTicketButton;
    private ProgressBar progressBar;

    private ApiService apiService;
    private final List<Category> categories = new ArrayList<>();
    private Uri selectedPhotoUri;
    private Uri pendingCameraPhotoUri;
    private Double currentLat;
    private Double currentLng;

    private final ActivityResultLauncher<String> galleryPhotoLauncher = registerForActivityResult(
            new ActivityResultContracts.GetContent(),
            uri -> {
                if (uri == null) {
                    Toast.makeText(this, "Фото не выбрано", Toast.LENGTH_SHORT).show();
                    return;
                }
                setSelectedPhoto(uri, "Фото выбрано из галереи");
            }
    );

    private final ActivityResultLauncher<Uri> cameraPhotoLauncher = registerForActivityResult(
            new ActivityResultContracts.TakePicture(),
            success -> {
                if (Boolean.TRUE.equals(success) && pendingCameraPhotoUri != null) {
                    setSelectedPhoto(pendingCameraPhotoUri, "Фото сделано с камеры");
                } else {
                    Toast.makeText(this, "Съёмка отменена", Toast.LENGTH_SHORT).show();
                }
            }
    );

    private final ActivityResultLauncher<String> cameraPermissionLauncher = registerForActivityResult(
            new ActivityResultContracts.RequestPermission(),
            granted -> {
                if (Boolean.TRUE.equals(granted)) {
                    openCamera();
                } else {
                    Toast.makeText(this, "Разрешение на камеру не выдано", Toast.LENGTH_LONG).show();
                }
            }
    );

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_create_ticket);

        categorySpinner = findViewById(R.id.categorySpinner);
        descriptionEditText = findViewById(R.id.descriptionEditText);
        locationStatusTextView = findViewById(R.id.locationStatusTextView);
        photoNameTextView = findViewById(R.id.photoNameTextView);
        photoPreviewImageView = findViewById(R.id.photoPreviewImageView);
        refreshLocationButton = findViewById(R.id.refreshLocationButton);
        pickMapButton = findViewById(R.id.pickMapButton);
        selectPhotoButton = findViewById(R.id.selectPhotoButton);
        sendTicketButton = findViewById(R.id.sendTicketButton);
        progressBar = findViewById(R.id.createProgressBar);

        String serverUrl = getSharedPreferences("auth", MODE_PRIVATE).getString("server_url", ApiClient.DEFAULT_BASE_URL);
        apiService = ApiClient.getClient(serverUrl).create(ApiService.class);

        selectPhotoButton.setOnClickListener(v -> showPhotoSourceDialog());
        refreshLocationButton.setOnClickListener(v -> requestLocationPermissionIfNeeded());
        pickMapButton.setOnClickListener(v -> openMapPicker());
        sendTicketButton.setOnClickListener(v -> createTicket());

        loadCategories();
        locationStatusTextView.setText("Место не выбрано");
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

                    ArrayAdapter<Category> adapter = new ArrayAdapter<>(
                            CreateTicketActivity.this,
                            android.R.layout.simple_spinner_item,
                            categories
                    );
                    adapter.setDropDownViewResource(android.R.layout.simple_spinner_dropdown_item);
                    categorySpinner.setAdapter(adapter);
                } else {
                    Toast.makeText(CreateTicketActivity.this, "Не удалось загрузить категории", Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onFailure(Call<CategoriesResponse> call, Throwable t) {
                setLoading(false);
                Toast.makeText(CreateTicketActivity.this, "Ошибка подключения: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
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
                detectLocation();
            } else {
                locationStatusTextView.setText("Выберите место на карте");
                Toast.makeText(this, "Разрешение не выдано. Выберите место на карте", Toast.LENGTH_LONG).show();
            }
        }
    }

    private void detectLocation() {
        LocationManager locationManager = (LocationManager) getSystemService(LOCATION_SERVICE);
        if (locationManager == null) {
            locationStatusTextView.setText("Выберите место на карте");
            return;
        }

        boolean fineGranted = ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED;
        boolean coarseGranted = ContextCompat.checkSelfPermission(this, Manifest.permission.ACCESS_COARSE_LOCATION) == PackageManager.PERMISSION_GRANTED;
        if (!fineGranted && !coarseGranted) {
            return;
        }

        boolean gpsEnabled = locationManager.isProviderEnabled(LocationManager.GPS_PROVIDER);
        boolean networkEnabled = locationManager.isProviderEnabled(LocationManager.NETWORK_PROVIDER);

        if (!gpsEnabled && !networkEnabled) {
            locationStatusTextView.setText("Включите геолокацию или выберите место на карте");
            Toast.makeText(this, "Включите геолокацию или выберите место на карте", Toast.LENGTH_LONG).show();
            return;
        }

        locationStatusTextView.setText("Определяем местоположение...");

        try {
            Location lastLocation = null;

            if (fineGranted && gpsEnabled) {
                lastLocation = locationManager.getLastKnownLocation(LocationManager.GPS_PROVIDER);
            }

            if (lastLocation == null && networkEnabled) {
                lastLocation = locationManager.getLastKnownLocation(LocationManager.NETWORK_PROVIDER);
            }

            if (lastLocation != null) {
                setCurrentLocation(lastLocation.getLatitude(), lastLocation.getLongitude(), "Местоположение определено автоматически");
            }

            String provider = null;
            if (fineGranted && gpsEnabled) {
                provider = LocationManager.GPS_PROVIDER;
            } else if (networkEnabled) {
                provider = LocationManager.NETWORK_PROVIDER;
            }

            if (provider == null) {
                locationStatusTextView.setText("Включите геолокацию или выберите место на карте");
                Toast.makeText(this, "Включите геолокацию или выберите место на карте", Toast.LENGTH_LONG).show();
                return;
            }

            LocationListener listener = new LocationListener() {
                @Override
                public void onLocationChanged(@NonNull Location location) {
                    setCurrentLocation(location.getLatitude(), location.getLongitude(), "Местоположение определено автоматически");
                    try {
                        locationManager.removeUpdates(this);
                    } catch (SecurityException ignored) {
                    }
                }
            };

            locationManager.requestSingleUpdate(provider, listener, Looper.getMainLooper());
        } catch (SecurityException e) {
            locationStatusTextView.setText("Выберите место на карте");
        }
    }

    private void setCurrentLocation(double lat, double lng, String sourceText) {
        currentLat = lat;
        currentLng = lng;
        locationStatusTextView.setText(sourceText);
    }

    private void openMapPicker() {
        Intent intent = new Intent(this, MapPickerActivity.class);
        if (currentLat != null && currentLng != null) {
            intent.putExtra("lat", currentLat);
            intent.putExtra("lng", currentLng);
        }
        startActivityForResult(intent, MAP_PICK_REQUEST);
    }

    private void showPhotoSourceDialog() {
        new MaterialAlertDialogBuilder(this)
                .setTitle("Выберите фото")
                .setItems(new CharSequence[]{"Камера", "Галерея"}, (dialog, which) -> {
                    if (which == 0) {
                        openCameraWithPermission();
                    } else {
                        galleryPhotoLauncher.launch("image/*");
                    }
                })
                .setNegativeButton("Отмена", null)
                .show();
    }

    private void openCameraWithPermission() {
        if (ContextCompat.checkSelfPermission(this, Manifest.permission.CAMERA) == PackageManager.PERMISSION_GRANTED) {
            openCamera();
        } else {
            cameraPermissionLauncher.launch(Manifest.permission.CAMERA);
        }
    }

    private void openCamera() {
        try {
            pendingCameraPhotoUri = createCameraPhotoUri();
            cameraPhotoLauncher.launch(pendingCameraPhotoUri);
        } catch (IOException e) {
            Toast.makeText(this, "Не удалось открыть камеру", Toast.LENGTH_LONG).show();
        }
    }

    private Uri createCameraPhotoUri() throws IOException {
        File dir = new File(getCacheDir(), "images");
        if (!dir.exists() && !dir.mkdirs()) {
            throw new IOException("Не удалось создать папку для фото");
        }

        File file = File.createTempFile("ticket_photo_", ".jpg", dir);
        return FileProvider.getUriForFile(this, getPackageName() + ".fileprovider", file);
    }

    private void setSelectedPhoto(Uri uri, String label) {
        selectedPhotoUri = uri;
        photoNameTextView.setText(label);
        photoPreviewImageView.setImageURI(uri);
        photoPreviewImageView.setVisibility(View.VISIBLE);
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, Intent data) {
        super.onActivityResult(requestCode, resultCode, data);

        if (requestCode == MAP_PICK_REQUEST && resultCode == RESULT_OK && data != null) {
            double lat = data.getDoubleExtra("lat", 0);
            double lng = data.getDoubleExtra("lng", 0);
            if (lat != 0 || lng != 0) {
                setCurrentLocation(lat, lng, "Точка выбрана на карте");
            }
        }
    }

    private void createTicket() {
        if (categories.isEmpty()) {
            Toast.makeText(this, "Категории ещё не загружены", Toast.LENGTH_SHORT).show();
            return;
        }

        if (selectedPhotoUri == null) {
            Toast.makeText(this, "Выберите фото загрязнения", Toast.LENGTH_SHORT).show();
            return;
        }

        if (currentLat == null || currentLng == null) {
            Toast.makeText(this, "Выберите место заявки", Toast.LENGTH_LONG).show();
            return;
        }

        String description = getText(descriptionEditText);
        Category category = (Category) categorySpinner.getSelectedItem();
        String token = getSharedPreferences("auth", MODE_PRIVATE).getString("token", "");
        if (token == null || token.trim().isEmpty()) {
            Toast.makeText(this, "Авторизация не найдена. Войдите заново", Toast.LENGTH_LONG).show();
            return;
        }

        if (category == null) {
            Toast.makeText(this, "Выберите категорию", Toast.LENGTH_SHORT).show();
            return;
        }

        try {
            File file = createTempFileFromUri(selectedPhotoUri);
            String mimeType = getContentResolver().getType(selectedPhotoUri);
            if (mimeType == null) {
                mimeType = "image/jpeg";
            }

            RequestBody photoBody = RequestBody.create(MediaType.parse(mimeType), file);
            MultipartBody.Part photoPart = MultipartBody.Part.createFormData("photo_before", file.getName(), photoBody);

            RequestBody categoryIdBody = textPart(String.valueOf(category.getId()));
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
                        Toast.makeText(CreateTicketActivity.this, "Не удалось создать заявку", Toast.LENGTH_LONG).show();
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
    }

    private File createTempFileFromUri(Uri uri) throws IOException {
        InputStream inputStream = getContentResolver().openInputStream(uri);
        if (inputStream == null) {
            throw new IOException("Не удалось открыть фото");
        }
        File file = new File(getCacheDir(), "ticket_photo_" + System.currentTimeMillis() + ".jpg");
        FileOutputStream outputStream = new FileOutputStream(file);

        byte[] buffer = new byte[4096];
        int read;
        while ((read = inputStream.read(buffer)) != -1) {
            outputStream.write(buffer, 0, read);
        }

        outputStream.flush();
        outputStream.close();
        inputStream.close();

        return file;
    }
}
