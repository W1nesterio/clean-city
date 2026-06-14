package com.example.cleancity.api;

import com.example.cleancity.models.CategoriesResponse;
import com.example.cleancity.models.CreateTicketResponse;
import com.example.cleancity.models.LoginRequest;
import com.example.cleancity.models.LoginResponse;
import com.example.cleancity.models.RegisterRequest;
import com.example.cleancity.models.TicketStatusRequest;
import com.example.cleancity.models.TicketsResponse;

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

public interface ApiService {
    @POST("login")
    Call<LoginResponse> login(@Body LoginRequest request);

    @POST("register")
    Call<LoginResponse> register(@Body RegisterRequest request);

    @GET("categories")
    Call<CategoriesResponse> getCategories();

    @GET("tickets/my")
    Call<TicketsResponse> getMyTickets(@Header("Authorization") String authorization);

    @GET("worker/tickets")
    Call<TicketsResponse> getWorkerTickets(@Header("Authorization") String authorization);

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

    @DELETE("tickets/{id}")
    Call<CreateTicketResponse> deleteTicket(
            @Header("Authorization") String authorization,
            @Path("id") int ticketId
    );

}
