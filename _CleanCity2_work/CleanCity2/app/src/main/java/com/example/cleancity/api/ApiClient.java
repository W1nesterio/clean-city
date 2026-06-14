package com.example.cleancity.api;

import java.util.concurrent.TimeUnit;

import okhttp3.OkHttpClient;
import okhttp3.logging.HttpLoggingInterceptor;
import retrofit2.Retrofit;
import retrofit2.converter.gson.GsonConverterFactory;

public class ApiClient {
    // Для разработки через USB + adb reverse.
    // Перед финальной сборкой здесь будет домен хостинга, например: https://clean-city.by/api/
    public static final String DEFAULT_BASE_URL = "http://127.0.0.1:8000/api/";

    private static Retrofit retrofit;
    private static String currentBaseUrl;

    public static Retrofit getClient() {
        return getClient(DEFAULT_BASE_URL);
    }

    public static Retrofit getClient(String baseUrl) {
        String normalizedBaseUrl = normalizeBaseUrl(baseUrl);

        if (retrofit == null || currentBaseUrl == null || !currentBaseUrl.equals(normalizedBaseUrl)) {
            HttpLoggingInterceptor loggingInterceptor = new HttpLoggingInterceptor();
            loggingInterceptor.setLevel(HttpLoggingInterceptor.Level.BODY);

            OkHttpClient client = new OkHttpClient.Builder()
                    .connectTimeout(30, TimeUnit.SECONDS)
                    .readTimeout(30, TimeUnit.SECONDS)
                    .writeTimeout(30, TimeUnit.SECONDS)
                    .addInterceptor(loggingInterceptor)
                    .build();

            retrofit = new Retrofit.Builder()
                    .baseUrl(normalizedBaseUrl)
                    .client(client)
                    .addConverterFactory(GsonConverterFactory.create())
                    .build();

            currentBaseUrl = normalizedBaseUrl;
        }

        return retrofit;
    }

    private static String normalizeBaseUrl(String baseUrl) {
        if (baseUrl == null || baseUrl.trim().isEmpty()) {
            return DEFAULT_BASE_URL;
        }

        String result = baseUrl.trim();

        if (!result.endsWith("/")) {
            result += "/";
        }

        if (!result.endsWith("api/")) {
            result += "api/";
        }

        return result;
    }
}
