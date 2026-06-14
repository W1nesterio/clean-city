package com.example.cleancity.api;

import com.example.cleancity.models.CategoriesResponse;
import com.example.cleancity.models.ChangePasswordRequest;
import com.example.cleancity.models.CityListResponse;
import com.example.cleancity.models.ClaimRewardResponse;
import com.example.cleancity.models.CreateTicketResponse;
import com.example.cleancity.models.LoginRequest;
import com.example.cleancity.models.LoginResponse;
import com.example.cleancity.models.NewsResponse;
import com.example.cleancity.models.PointsResponse;
import com.example.cleancity.models.RegisterRequest;
import com.example.cleancity.models.RewardsResponse;
import com.example.cleancity.models.TicketStatusRequest;
import com.example.cleancity.models.SimpleResponse;
import com.example.cleancity.models.TicketsResponse;
import com.example.cleancity.models.WorkerRegisterRequest;

import okhttp3.MultipartBody;
import okhttp3.RequestBody;
import retrofit2.Call;
import retrofit2.http.Body;
import retrofit2.http.DELETE;
import retrofit2.http.GET;
import retrofit2.http.Header;
import retrofit2.http.Multipart;
import retrofit2.http.POST;
import retrofit2.http.Part;
import retrofit2.http.Path;
import retrofit2.http.Query;

public interface ApiService {
    @POST("login")
    Call<LoginResponse> login(@Body LoginRequest request);

    @POST("register")
    Call<LoginResponse> register(@Body RegisterRequest request);

    @POST("register-worker")
    Call<LoginResponse> registerWorker(@Body WorkerRegisterRequest request);

    @GET("categories")
    Call<CategoriesResponse> getCategories();

    @GET("cities")
    Call<CityListResponse> getCities();

    @GET("news")
    Call<NewsResponse> getNews(@Query("city_id") Integer cityId);

    @GET("rewards")
    Call<RewardsResponse> getRewards(@Query("city_id") Integer cityId);

    @POST("rewards/{id}/claim")
    Call<ClaimRewardResponse> claimReward(
            @Header("Authorization") String authorization,
            @Path("id") int rewardId
    );

    @GET("me/points")
    Call<PointsResponse> getMyPoints(@Header("Authorization") String authorization);

    @GET("tickets/my")
    Call<TicketsResponse> getMyTickets(@Header("Authorization") String authorization);

    @GET("worker/tickets")
    Call<TicketsResponse> getWorkerTickets(@Header("Authorization") String authorization);

    @GET("worker/available-tickets")
    Call<TicketsResponse> getAvailableTickets(@Header("Authorization") String authorization);

    @POST("tickets/{id}/claim-request")
    Call<SimpleResponse> requestTicketClaim(
            @Header("Authorization") String authorization,
            @Path("id") int ticketId
    );

    @GET("profile")
    Call<LoginResponse> getProfile(@Header("Authorization") String authorization);

    @Multipart
    @POST("profile")
    Call<LoginResponse> updateProfile(
            @Header("Authorization") String authorization,
            @Part MultipartBody.Part avatar
    );

    @DELETE("profile/avatar")
    Call<LoginResponse> deleteAvatar(@Header("Authorization") String authorization);

    @POST("profile/password")
    Call<SimpleResponse> changePassword(
            @Header("Authorization") String authorization,
            @Body ChangePasswordRequest request
    );

    @POST("tickets/{id}/status")
    Call<CreateTicketResponse> changeStatus(
            @Header("Authorization") String authorization,
            @Path("id") int ticketId,
            @Body TicketStatusRequest request
    );

    @Multipart
    @POST("tickets")
    Call<CreateTicketResponse> createTicket(
            @Header("Authorization") String authorization,
            @Part("category_id") RequestBody categoryId,
            @Part("lat") RequestBody lat,
            @Part("lng") RequestBody lng,
            @Part("description") RequestBody description,
            @Part("priority") RequestBody priority,
            @Part MultipartBody.Part photoBefore
    );

    @Multipart
    @POST("tickets/{id}/photo-after")
    Call<CreateTicketResponse> completeTicket(
            @Header("Authorization") String authorization,
            @Path("id") int ticketId,
            @Part("comment") RequestBody comment,
            @Part MultipartBody.Part photoAfter
    );

    @GET("resident/available-tasks")
    Call<TicketsResponse> getResidentAvailableTasks(@Header("Authorization") String authorization);

    @POST("tickets/{id}/resident-accept")
    Call<SimpleResponse> residentAcceptTicket(
            @Header("Authorization") String authorization,
            @Path("id") int ticketId
    );

    @DELETE("tickets/{id}")
    Call<SimpleResponse> deleteTicket(
            @Header("Authorization") String authorization,
            @Path("id") int ticketId
    );
}
