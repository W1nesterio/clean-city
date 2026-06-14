package com.example.cleancity.ui;

import android.app.Activity;
import android.content.Context;
import android.content.SharedPreferences;
import android.content.res.ColorStateList;
import android.content.res.Configuration;
import android.graphics.Color;
import android.view.View;
import android.view.ViewGroup;
import android.widget.EditText;
import android.widget.RadioButton;
import android.widget.TextView;

import androidx.appcompat.app.AppCompatDelegate;

import com.google.android.material.button.MaterialButton;
import com.google.android.material.card.MaterialCardView;
import com.google.android.material.textfield.TextInputLayout;

public final class AppUi {
    private static final String PREFS = "app_settings";

    private AppUi() { }

    public static SharedPreferences prefs(Context context) {
        return context.getSharedPreferences(PREFS, Context.MODE_PRIVATE);
    }

    public static String language(Context context) {
        return prefs(context).getString("language", "ru");
    }

    public static String theme(Context context) {
        return prefs(context).getString("theme", "light");
    }

    public static boolean dark(Context context) {
        String value = theme(context);
        if ("dark".equals(value)) {
            return true;
        }
        if ("system".equals(value)) {
            int mask = context.getResources().getConfiguration().uiMode & Configuration.UI_MODE_NIGHT_MASK;
            return mask == Configuration.UI_MODE_NIGHT_YES;
        }
        return false;
    }

    public static void applyTheme(Context context) {
        String theme = theme(context);
        if ("dark".equals(theme)) {
            AppCompatDelegate.setDefaultNightMode(AppCompatDelegate.MODE_NIGHT_YES);
        } else if ("system".equals(theme)) {
            AppCompatDelegate.setDefaultNightMode(AppCompatDelegate.MODE_NIGHT_FOLLOW_SYSTEM);
        } else {
            AppCompatDelegate.setDefaultNightMode(AppCompatDelegate.MODE_NIGHT_NO);
        }
    }

    public static void applyAuthTheme(Context context) {
        applyTheme(context);
    }

    public static void resetAuthMode(Context context) {
        // Accessibility auth mode was removed.
        // This method is kept so older HomeActivity versions compile safely.
    }

    public static void applyAll(Activity activity, String screenRu, String screenBe) {
        apply(activity);
    }

    public static void applyAuthAll(Activity activity, String screenRu, String screenBe) {
        apply(activity);
    }

    public static void apply(Activity activity) {
        View root = activity.findViewById(android.R.id.content);
        if (root != null) {
            applyToView(activity, root);
        }
    }

    public static void applyAuth(Activity activity) {
        apply(activity);
    }

    public static void applyToView(Context context, View view) {
        if (view == null || !dark(context)) {
            return;
        }
        applyDarkPalette(context, view);
    }

    private static void applyDarkPalette(Context context, View view) {
        int bg = Color.parseColor("#0D1712");
        int card = Color.parseColor("#14241D");
        int text = Color.parseColor("#F8FAFC");
        int muted = Color.parseColor("#CBD5E1");
        int line = Color.parseColor("#315242");
        int field = Color.parseColor("#102019");

        if (view.getId() == android.R.id.content || view instanceof ViewGroup) {
            Object tag = view.getTag();
            if (tag == null || !"keep_background".equals(tag.toString())) {
                view.setBackgroundColor(bg);
            }
        }

        if (view instanceof MaterialCardView) {
            MaterialCardView cardView = (MaterialCardView) view;
            cardView.setCardBackgroundColor(card);
            cardView.setStrokeColor(line);
            cardView.setStrokeWidth(dp(context, 1));
        } else if (view instanceof TextInputLayout) {
            TextInputLayout inputLayout = (TextInputLayout) view;
            inputLayout.setBoxBackgroundColor(field);
            inputLayout.setBoxStrokeColor(line);
            inputLayout.setHintTextColor(ColorStateList.valueOf(muted));
        } else if (view instanceof EditText) {
            EditText editText = (EditText) view;
            editText.setTextColor(text);
            editText.setHintTextColor(muted);
        } else if (view instanceof MaterialButton) {
            // Button colors are defined in layouts.
        } else if (view instanceof RadioButton) {
            ((RadioButton) view).setTextColor(text);
        } else if (view instanceof TextView) {
            TextView tv = (TextView) view;
            int currentColor = tv.getCurrentTextColor();
            if (currentColor != Color.WHITE && currentColor != Color.parseColor("#FFFFFF")) {
                tv.setTextColor(text);
            }
        }

        if (view instanceof ViewGroup) {
            ViewGroup group = (ViewGroup) view;
            for (int i = 0; i < group.getChildCount(); i++) {
                applyDarkPalette(context, group.getChildAt(i));
            }
        }
    }

    public static void feedback(Context context, View view) {
        // Intentionally empty.
    }

    public static void speak(Context context, String text) {
        // Intentionally empty.
    }

    public static void speakAuth(Context context, String text) {
        // Intentionally empty.
    }

    public static void speakAny(Context context, String text, boolean enabled) {
        // Intentionally empty.
    }

    public static String t(Context context, String ru, String be) {
        return "be".equals(language(context)) ? be : ru;
    }

    public static int dp(Context context, int value) {
        return (int) (value * context.getResources().getDisplayMetrics().density + 0.5f);
    }
}
