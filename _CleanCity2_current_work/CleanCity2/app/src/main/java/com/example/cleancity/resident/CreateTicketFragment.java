package com.example.cleancity.resident;

import android.Manifest;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.pm.PackageManager;
import android.content.res.ColorStateList;
import android.location.Location;
import android.location.LocationManager;
import android.net.Uri;
import android.os.Bundle;
import android.os.Handler;
import android.os.Looper;
import android.provider.MediaStore;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.LinearLayout;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.core.content.ContextCompat;
import androidx.core.content.FileProvider;
import androidx.fragment.app.Fragment;

import com.example.cleancity.MapPickerActivity;
import com.example.cleancity.R;
import com.example.cleancity.ResidentContainerActivity;
import com.example.cleancity.api.ApiClient;
import com.example.cleancity.api.ApiService;
import com.example.cleancity.models.CategoriesResponse;
import com.example.cleancity.models.Category;
import com.example.cleancity.models.CreateTicketResponse;
import com.example.cleancity.ui.AppUi;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.dialog.MaterialAlertDialogBuilder;
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

public class CreateTicketFragment extends Fragment {

    private static final int PICK_IMAGE_REQUEST  = 1001;
    private static final int TAKE_IMAGE_REQUEST  = 1003;
    private static final int MAP_PICK_REQUEST    = 1002;
    private static final int LOC_PERM_REQUEST    = 2001;
    private static final int CAMERA_PERM_REQUEST = 2002;

    private LinearLayout categoryButtonsContainer;
    private TextInputEditText descriptionEditText;
    private TextView locationStatusTextView, photoNameTextView;
    private ImageView photoPreviewImageView;
    private MaterialButton refreshLocationButton, pickMapButton, selectPhotoButton, sendTicketButton;
    private ProgressBar progressBar;

    private ApiService apiService;
    private String token;
    private final List<Category> categories = new ArrayList<>();
    private final List<MaterialButton> categoryButtons = new ArrayList<>();
    private Uri selectedPhotoUri;
    private Uri cameraPhotoUri;
    private File cameraPhotoFile;
    private Double currentLat, currentLng;
    private Category selectedCategory;
    private boolean openMapAfterPermission;

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        return inflater.inflate(R.layout.activity_create_ticket, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View v, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(v, savedInstanceState);

        // Hide the back button - navigation handled by ViewPager2 swipe
        View backBtn = v.findViewById(R.id.createBackButton);
        if (backBtn != null) backBtn.setVisibility(View.GONE);

        categoryButtonsContainer = v.findViewById(R.id.categoryButtonsContainer);
        descriptionEditText      = v.findViewById(R.id.descriptionEditText);
        locationStatusTextView   = v.findViewById(R.id.locationStatusTextView);
        photoNameTextView        = v.findViewById(R.id.photoNameTextView);
        photoPreviewImageView    = v.findViewById(R.id.photoPreviewImageView);
        refreshLocationButton    = v.findViewById(R.id.refreshLocationButton);
        pickMapButton            = v.findViewById(R.id.pickMapButton);
        selectPhotoButton        = v.findViewById(R.id.selectPhotoButton);
        sendTicketButton         = v.findViewById(R.id.sendTicketButton);
        progressBar              = v.findViewById(R.id.createProgressBar);

        SharedPreferences prefs = requireContext().getSharedPreferences("auth", Context.MODE_PRIVATE);
        token = prefs.getString("token", "");
        String serverUrl = prefs.getString("server_url", ApiClient.DEFAULT_BASE_URL);
        apiService = ApiClient.getClient(serverUrl).create(ApiService.class);

        selectPhotoButton.setOnClickListener(lv -> showPhotoSourceDialog());
        refreshLocationButton.setOnClickListener(lv -> requestLocation(false));
        pickMapButton.setOnClickListener(lv -> requestLocation(true));
        sendTicketButton.setOnClickListener(lv -> createTicket());

        loadCategories();
        locationStatusTextView.setText("Не выбрано");
        requestLocation(false);
    }

    @Override
    public void onHiddenChanged(boolean hidden) {
        super.onHiddenChanged(hidden);
        if (!hidden) loadCategories();
    }

    // ─── Location ──────────────────────────────────────────────
    private void requestLocation(boolean openMapAfter) {
        openMapAfterPermission = openMapAfter;
        boolean ok = ContextCompat.checkSelfPermission(requireContext(), Manifest.permission.ACCESS_FINE_LOCATION) == PackageManager.PERMISSION_GRANTED
                  || ContextCompat.checkSelfPermission(requireContext(), Manifest.permission.ACCESS_COARSE_LOCATION) == PackageManager.PERMISSION_GRANTED;
        if (ok) {
            if (openMapAfter) detectAndOpenMap(); else detectLocation();
        } else {
            requestPermissions(new String[]{Manifest.permission.ACCESS_FINE_LOCATION, Manifest.permission.ACCESS_COARSE_LOCATION}, LOC_PERM_REQUEST);
        }
    }

    @Override
    public void onRequestPermissionsResult(int req, @NonNull String[] perms, @NonNull int[] grants) {
        if (req == LOC_PERM_REQUEST) {
            boolean granted = false;
            for (int g : grants) if (g == PackageManager.PERMISSION_GRANTED) { granted = true; break; }
            if (granted) {
                if (openMapAfterPermission) { openMapAfterPermission = false; detectAndOpenMap(); }
                else detectLocation();
            } else {
                locationStatusTextView.setText("Выберите место на карте");
                Toast.makeText(requireContext(), "Разрешение не выдано. Выберите место на карте", Toast.LENGTH_LONG).show();
                if (openMapAfterPermission) {
                    openMapAfterPermission = false;
                    openMapPicker();
                }
            }
            return;
        }

        if (req == CAMERA_PERM_REQUEST) {
            boolean granted = grants.length > 0 && grants[0] == PackageManager.PERMISSION_GRANTED;
            if (granted) {
                openCamera();
            } else {
                Toast.makeText(requireContext(), "Разрешение на камеру не выдано", Toast.LENGTH_LONG).show();
            }
        }
    }

    @SuppressWarnings("MissingPermission")
    private void detectLocation() {
        LocationManager lm = (LocationManager) requireContext().getSystemService(Context.LOCATION_SERVICE);
        if (lm == null) { locationStatusTextView.setText("Выберите на карте"); return; }
        if (!lm.isProviderEnabled(LocationManager.GPS_PROVIDER) && !lm.isProviderEnabled(LocationManager.NETWORK_PROVIDER)) {
            locationStatusTextView.setText("Включите геолокацию или выберите место на карте");
            Toast.makeText(requireContext(), "Включите геолокацию или выберите место на карте", Toast.LENGTH_LONG).show();
            return;
        }
        locationStatusTextView.setText("Определяем...");
        try {
            Location last = getLastKnown(lm);
            if (last != null) { setLoc(last.getLatitude(), last.getLongitude()); return; }
            String provider = bestProvider(lm);
            if (provider == null) { locationStatusTextView.setText("Включите геолокацию или выберите место на карте"); return; }
            lm.requestSingleUpdate(provider, loc -> setLoc(loc.getLatitude(), loc.getLongitude()), Looper.getMainLooper());
        } catch (SecurityException e) { locationStatusTextView.setText("Выберите на карте"); }
    }

    @SuppressWarnings("MissingPermission")
    private void detectAndOpenMap() {
        LocationManager lm = (LocationManager) requireContext().getSystemService(Context.LOCATION_SERVICE);
        if (lm == null) { openMapPicker(); return; }
        locationStatusTextView.setText("Определяем...");
        try {
            Location last = getLastKnown(lm);
            if (last != null) { setLoc(last.getLatitude(), last.getLongitude()); openMapPicker(); return; }
            String provider = bestProvider(lm);
            if (provider == null) { openMapPicker(); return; }
            Handler h = new Handler(Looper.getMainLooper());
            boolean[] opened = {false};
            Runnable fallback = () -> { if (!opened[0]) { opened[0] = true; openMapPicker(); } };
            h.postDelayed(fallback, 3500);
            lm.requestSingleUpdate(provider, loc -> {
                if (!opened[0]) { opened[0] = true; h.removeCallbacks(fallback); setLoc(loc.getLatitude(), loc.getLongitude()); openMapPicker(); }
                try { lm.removeUpdates(location -> {}); } catch (Exception ignored) {}
            }, Looper.getMainLooper());
        } catch (SecurityException e) { openMapPicker(); }
    }

    @SuppressWarnings("MissingPermission")
    private Location getLastKnown(LocationManager lm) throws SecurityException {
        Location l = null;
        if (lm.isProviderEnabled(LocationManager.GPS_PROVIDER)) l = lm.getLastKnownLocation(LocationManager.GPS_PROVIDER);
        if (l == null && lm.isProviderEnabled(LocationManager.NETWORK_PROVIDER)) l = lm.getLastKnownLocation(LocationManager.NETWORK_PROVIDER);
        return l;
    }

    private String bestProvider(LocationManager lm) {
        if (lm.isProviderEnabled(LocationManager.GPS_PROVIDER)) return LocationManager.GPS_PROVIDER;
        if (lm.isProviderEnabled(LocationManager.NETWORK_PROVIDER)) return LocationManager.NETWORK_PROVIDER;
        return null;
    }

    private void setLoc(double lat, double lng) { setLoc(lat, lng, "Место определено автоматически"); }

    private void setLoc(double lat, double lng, String text) {
        currentLat = lat;
        currentLng = lng;
        locationStatusTextView.setText(text);
    }

    private void openMapPicker() {
        Intent i = new Intent(requireActivity(), MapPickerActivity.class);
        if (currentLat != null) { i.putExtra("lat", currentLat); i.putExtra("lng", currentLng); }
        startActivityForResult(i, MAP_PICK_REQUEST);
    }

    // ─── Photo ────────────────────────────────────────────────
    private void showPhotoSourceDialog() {
        new MaterialAlertDialogBuilder(requireContext())
                .setTitle("Выберите фото")
                .setItems(new CharSequence[]{"Камера", "Галерея"}, (dialog, which) -> {
                    if (which == 0) {
                        openCameraWithPermission();
                    } else {
                        openImagePicker();
                    }
                })
                .setNegativeButton("Отмена", null)
                .show();
    }

    private void openImagePicker() {
        Intent i = new Intent(Intent.ACTION_GET_CONTENT);
        i.setType("image/*");
        startActivityForResult(Intent.createChooser(i, "Фото заявки"), PICK_IMAGE_REQUEST);
    }

    private void openCameraWithPermission() {
        if (ContextCompat.checkSelfPermission(requireContext(), Manifest.permission.CAMERA) == PackageManager.PERMISSION_GRANTED) {
            openCamera();
        } else {
            requestPermissions(new String[]{Manifest.permission.CAMERA}, CAMERA_PERM_REQUEST);
        }
    }

    private void openCamera() {
        try {
            cameraPhotoFile = createImageFile("ticket_photo_");
            cameraPhotoUri = FileProvider.getUriForFile(
                    requireContext(),
                    requireContext().getPackageName() + ".fileprovider",
                    cameraPhotoFile
            );

            Intent intent = new Intent(MediaStore.ACTION_IMAGE_CAPTURE);
            intent.putExtra(MediaStore.EXTRA_OUTPUT, cameraPhotoUri);
            intent.addFlags(Intent.FLAG_GRANT_READ_URI_PERMISSION | Intent.FLAG_GRANT_WRITE_URI_PERMISSION);

            if (intent.resolveActivity(requireActivity().getPackageManager()) == null) {
                Toast.makeText(requireContext(), "Камера недоступна", Toast.LENGTH_LONG).show();
                return;
            }

            startActivityForResult(intent, TAKE_IMAGE_REQUEST);
        } catch (Exception e) {
            Toast.makeText(requireContext(), "Не удалось открыть камеру", Toast.LENGTH_LONG).show();
        }
    }

    private File createImageFile(String prefix) throws IOException {
        File dir = new File(requireContext().getCacheDir(), "photos");
        if (!dir.exists() && !dir.mkdirs()) {
            throw new IOException("Не удалось создать папку для фото");
        }
        return File.createTempFile(prefix + System.currentTimeMillis(), ".jpg", dir);
    }

    private void setSelectedPhoto(Uri uri, String label) {
        selectedPhotoUri = uri;
        photoNameTextView.setText(label);
        photoPreviewImageView.setImageURI(uri);
        photoPreviewImageView.setVisibility(View.VISIBLE);
    }

    @Override
    public void onActivityResult(int req, int res, @Nullable Intent data) {
        super.onActivityResult(req, res, data);
        if (req == PICK_IMAGE_REQUEST && res == android.app.Activity.RESULT_OK && data != null && data.getData() != null) {
            setSelectedPhoto(data.getData(), "Фото выбрано из галереи");
            return;
        }
        if (req == TAKE_IMAGE_REQUEST && res == android.app.Activity.RESULT_OK && cameraPhotoUri != null) {
            setSelectedPhoto(cameraPhotoUri, "Фото сделано с камеры");
            return;
        }
        if (req == TAKE_IMAGE_REQUEST) {
            cameraPhotoUri = null;
            cameraPhotoFile = null;
        }
        if (req == MAP_PICK_REQUEST && res == android.app.Activity.RESULT_OK && data != null) {
            double lat = data.getDoubleExtra("lat", 0), lng = data.getDoubleExtra("lng", 0);
            if (lat != 0 || lng != 0) setLoc(lat, lng, "Точка выбрана на карте");
        }
    }

    // ─── Categories ───────────────────────────────────────────
    private void loadCategories() {
        if (apiService == null) return;
        setLoading(true);
        apiService.getCategories().enqueue(new Callback<CategoriesResponse>() {
            @Override
            public void onResponse(Call<CategoriesResponse> c, Response<CategoriesResponse> r) {
                setLoading(false);
                if (!isAdded()) return;
                if (r.isSuccessful() && r.body() != null && r.body().getCategories() != null) {
                    categories.clear(); categories.addAll(r.body().getCategories()); buildButtons();
                }
            }
            @Override public void onFailure(Call<CategoriesResponse> c, Throwable t) { if (isAdded()) setLoading(false); }
        });
    }

    private void buildButtons() {
        categoryButtonsContainer.removeAllViews(); categoryButtons.clear();
        selectedCategory = categories.isEmpty() ? null : categories.get(0);
        for (Category cat : categories) {
            MaterialButton b = new MaterialButton(requireContext());
            b.setText(cat.getName()); b.setAllCaps(false); b.setTextSize(16);
            b.setCornerRadius(dp(16)); b.setInsetTop(0); b.setInsetBottom(0); b.setStrokeWidth(dp(1)); b.setTag(cat);
            LinearLayout.LayoutParams p = new LinearLayout.LayoutParams(LinearLayout.LayoutParams.MATCH_PARENT, dp(54));
            p.setMargins(0, 0, 0, dp(8)); categoryButtonsContainer.addView(b, p); categoryButtons.add(b);
            b.setOnClickListener(lv -> { selectedCategory = (Category) lv.getTag(); updateButtons(); });
        }
        updateButtons();
        AppUi.applyToView(requireContext(), categoryButtonsContainer);
    }

    private void updateButtons() {
        int green = ContextCompat.getColor(requireContext(), R.color.green_main);
        int white = ContextCompat.getColor(requireContext(), R.color.white);
        int dark  = ContextCompat.getColor(requireContext(), R.color.text_dark);
        int line  = ContextCompat.getColor(requireContext(), R.color.line_light);
        for (MaterialButton b : categoryButtons) {
            Category c = (Category) b.getTag();
            boolean sel = selectedCategory != null && c.getId() == selectedCategory.getId();
            b.setBackgroundTintList(ColorStateList.valueOf(sel ? green : white));
            b.setTextColor(sel ? white : dark);
            b.setStrokeColor(ColorStateList.valueOf(sel ? green : line));
        }
    }

    // ─── Submit ───────────────────────────────────────────────
    private void createTicket() {
        if (selectedCategory == null)  { Toast.makeText(requireContext(), "Выберите категорию", Toast.LENGTH_SHORT).show(); return; }
        if (selectedPhotoUri == null)   { Toast.makeText(requireContext(), "Выберите фото", Toast.LENGTH_SHORT).show(); return; }
        if (currentLat == null)         { Toast.makeText(requireContext(), "Выберите место", Toast.LENGTH_LONG).show(); return; }
        if (token == null || token.trim().isEmpty()) {
            Toast.makeText(requireContext(), "Авторизация не найдена. Войдите заново", Toast.LENGTH_LONG).show();
            return;
        }

        String desc = descriptionEditText.getText() != null ? descriptionEditText.getText().toString().trim() : "";
        if (desc.length() > 200) desc = desc.substring(0, 200);

        try {
            InputStream is = requireActivity().getContentResolver().openInputStream(selectedPhotoUri);
            if (is == null) {
                Toast.makeText(requireContext(), "Не удалось прочитать фото", Toast.LENGTH_LONG).show();
                return;
            }
            File file = new File(requireContext().getCacheDir(), "ticket_" + System.currentTimeMillis() + ".jpg");
            FileOutputStream fos = new FileOutputStream(file);
            byte[] buf = new byte[4096]; int read;
            while ((read = is.read(buf)) != -1) fos.write(buf, 0, read);
            fos.flush(); fos.close(); is.close();

            String mime = requireActivity().getContentResolver().getType(selectedPhotoUri);
            if (mime == null) mime = "image/jpeg";
            RequestBody photoBody = RequestBody.create(MediaType.parse(mime), file);
            MultipartBody.Part photoPart = MultipartBody.Part.createFormData("photo_before", file.getName(), photoBody);

            setLoading(true);
            apiService.createTicket("Bearer " + token,
                    text(String.valueOf(selectedCategory.getId())),
                    text(String.format(Locale.US, "%.7f", currentLat)),
                    text(String.format(Locale.US, "%.7f", currentLng)),
                    text(desc), text("normal"), photoPart
            ).enqueue(new Callback<CreateTicketResponse>() {
                @Override
                public void onResponse(Call<CreateTicketResponse> c, Response<CreateTicketResponse> r) {
                    if (!isAdded()) return;
                    setLoading(false);
                    if (r.isSuccessful() && r.body() != null) {
                        Toast.makeText(requireContext(), "Заявка создана!", Toast.LENGTH_LONG).show();
                        // Reset form
                        selectedPhotoUri = null; currentLat = null; currentLng = null; selectedCategory = null;
                        if (photoPreviewImageView != null) photoPreviewImageView.setVisibility(View.GONE);
                        if (locationStatusTextView != null) locationStatusTextView.setText("Не выбрано");
                        if (descriptionEditText != null) descriptionEditText.setText("");
                        // Switch to History tab
                        if (requireActivity() instanceof ResidentContainerActivity)
                            ((ResidentContainerActivity) requireActivity()).switchToTab(ResidentContainerActivity.TAB_HISTORY);
                    } else {
                        Toast.makeText(requireContext(), "Заявка не отправлена", Toast.LENGTH_LONG).show();
                    }
                }
                @Override
                public void onFailure(Call<CreateTicketResponse> c, Throwable t) {
                    if (isAdded()) { setLoading(false); Toast.makeText(requireContext(), "Ошибка: " + t.getMessage(), Toast.LENGTH_LONG).show(); }
                }
            });
        } catch (IOException e) { Toast.makeText(requireContext(), "Не удалось прочитать фото", Toast.LENGTH_LONG).show(); }
    }

    private void setLoading(boolean on) {
        if (progressBar != null)  progressBar.setVisibility(on ? View.VISIBLE : View.GONE);
        if (sendTicketButton != null) sendTicketButton.setEnabled(!on);
    }

    private RequestBody text(String v) { return RequestBody.create(MediaType.parse("text/plain"), v == null ? "" : v); }

    private int dp(int v) { return (int) (v * requireContext().getResources().getDisplayMetrics().density + 0.5f); }
}
