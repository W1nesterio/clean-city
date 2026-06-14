package com.example.cleancity.resident;

import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.RadioGroup;
import android.widget.TextView;
import android.widget.Toast;

import androidx.annotation.NonNull;
import androidx.annotation.Nullable;
import androidx.fragment.app.Fragment;

import com.example.cleancity.MainActivity;
import com.example.cleancity.R;
import com.example.cleancity.api.ApiClient;
import com.example.cleancity.api.ApiService;
import com.example.cleancity.models.ChangePasswordRequest;
import com.example.cleancity.models.SimpleResponse;
import com.example.cleancity.ui.AppUi;
import com.google.android.material.button.MaterialButton;
import com.google.android.material.textfield.TextInputEditText;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class SettingsResidentFragment extends Fragment {

    private SharedPreferences appPrefs, authPrefs;
    private ApiService apiService;
    private String token;

    private RadioGroup themeRadioGroup, langRadioGroup;
    private TextInputEditText currentPwInput, newPwInput, confirmPwInput;

    @Nullable
    @Override
    public View onCreateView(@NonNull LayoutInflater inflater, @Nullable ViewGroup container, @Nullable Bundle savedInstanceState) {
        return inflater.inflate(R.layout.activity_settings, container, false);
    }

    @Override
    public void onViewCreated(@NonNull View v, @Nullable Bundle savedInstanceState) {
        super.onViewCreated(v, savedInstanceState);

        // Hide bottom nav (handled by container)
        hideId(v, R.id.bottomNavContainer);
        // Hide back button
        View back = v.findViewById(R.id.settingsBackButton);
        if (back != null) back.setVisibility(View.GONE);

        appPrefs  = AppUi.prefs(requireContext());
        authPrefs = requireContext().getSharedPreferences("auth", Context.MODE_PRIVATE);
        token = authPrefs.getString("token", "");
        String serverUrl = authPrefs.getString("server_url", ApiClient.DEFAULT_BASE_URL);
        apiService = ApiClient.getClient(serverUrl).create(ApiService.class);

        themeRadioGroup = v.findViewById(R.id.themeRadioGroup);
        langRadioGroup  = v.findViewById(R.id.languageRadioGroup);
        currentPwInput  = v.findViewById(R.id.currentPasswordInput);
        newPwInput      = v.findViewById(R.id.newPasswordInput);
        confirmPwInput  = v.findViewById(R.id.confirmPasswordInput);
        MaterialButton changePwBtn = v.findViewById(R.id.changePasswordButton);
        MaterialButton logoutBtn   = v.findViewById(R.id.logoutProfileButton);

        // Profile info
        fillProfile(v);
        bindTheme();
        bindLanguage(v);

        if (changePwBtn != null) changePwBtn.setOnClickListener(lv -> changePassword());
        if (logoutBtn != null)   logoutBtn.setOnClickListener(lv -> logout());
    }

    private void fillProfile(View root) {
        String name  = authPrefs.getString("name", "");
        String email = authPrefs.getString("email", "");
        TextView tv;
        tv = root.findViewById(R.id.profileNameTextView);  if (tv != null) tv.setText(name.isEmpty() ? "—" : name);
        tv = root.findViewById(R.id.profileEmailTextView); if (tv != null) tv.setText(email.isEmpty() ? "—" : email);
        tv = root.findViewById(R.id.profileRoleTextView);  if (tv != null) tv.setText("Житель");
    }

    private void bindTheme() {
        if (themeRadioGroup == null) return;
        String theme = appPrefs.getString("theme", "light");
        if ("dark".equals(theme))    themeRadioGroup.check(R.id.themeDarkRadio);
        else if ("system".equals(theme)) themeRadioGroup.check(R.id.themeSystemRadio);
        else                          themeRadioGroup.check(R.id.themeLightRadio);

        themeRadioGroup.setOnCheckedChangeListener((g, id) -> {
            String val = "light";
            if (id == R.id.themeDarkRadio)   val = "dark";
            if (id == R.id.themeSystemRadio) val = "system";
            appPrefs.edit().putString("theme", val).apply();
            AppUi.applyTheme(requireContext());
            requireActivity().recreate();
        });
    }

    private void bindLanguage(View root) {
        if (langRadioGroup == null) return;
        String lang = appPrefs.getString("language", "ru");
        langRadioGroup.check("be".equals(lang) ? R.id.langBeRadio : R.id.langRuRadio);
        langRadioGroup.setOnCheckedChangeListener((g, id) -> {
            appPrefs.edit().putString("language", id == R.id.langBeRadio ? "be" : "ru").apply();
            Toast.makeText(requireContext(), AppUi.t(requireContext(), "Язык изменён", "Мова зменена"), Toast.LENGTH_SHORT).show();
        });
    }

    private void changePassword() {
        if (currentPwInput == null || newPwInput == null || confirmPwInput == null) return;
        String cur  = text(currentPwInput);
        String nw   = text(newPwInput);
        String conf = text(confirmPwInput);
        if (cur.isEmpty() || nw.isEmpty() || conf.isEmpty()) { toast("Заполните все поля"); return; }
        if (nw.length() < 6) { toast("Минимум 6 символов"); return; }
        if (!nw.equals(conf)) { toast("Пароли не совпадают"); return; }

        apiService.changePassword("Bearer " + token, new ChangePasswordRequest(cur, nw))
                .enqueue(new Callback<SimpleResponse>() {
                    @Override
                    public void onResponse(Call<SimpleResponse> c, Response<SimpleResponse> r) {
                        if (!isAdded()) return;
                        if (r.isSuccessful()) {
                            toast("Пароль изменён");
                            currentPwInput.setText(""); newPwInput.setText(""); confirmPwInput.setText("");
                        } else if (r.code() == 422) {
                            toast("Неверный текущий пароль");
                        } else {
                            toast("Ошибка: " + r.code());
                        }
                    }
                    @Override public void onFailure(Call<SimpleResponse> c, Throwable t) { if (isAdded()) toast("Ошибка соединения"); }
                });
    }

    private void logout() {
        authPrefs.edit().clear().apply();
        Intent i = new Intent(requireActivity(), MainActivity.class);
        i.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
        startActivity(i);
    }

    private String text(TextInputEditText e) { return e.getText() != null ? e.getText().toString().trim() : ""; }
    private void toast(String msg) { Toast.makeText(requireContext(), msg, Toast.LENGTH_SHORT).show(); }
    private void hideId(View root, int id) { View v = root.findViewById(id); if (v != null) v.setVisibility(View.GONE); }
}
