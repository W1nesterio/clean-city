# Запуск мобильного приложения через Cloudflare Tunnel

В проекте API-адрес сейчас задан в одном месте:

`app/src/main/java/com/example/cleancity/api/ApiClient.java`

Текущий адрес:

```java
public static final String DEFAULT_BASE_URL = "https://bracket-movements-measure-normally.trycloudflare.com/api/";
```

Если Cloudflare выдал новую ссылку, поменяй только эту строку и нажми Run в Android Studio.

Перед запуском приложения должны работать:

1. Laravel:

```powershell
cd D:\xamp\htdocs\clean-city-app
D:\xamp\php\php.exe artisan serve --host=127.0.0.1 --port=8000
```

2. Cloudflare Tunnel:

```powershell
cd D:\cloud
.\cloudflared.exe tunnel --url http://127.0.0.1:8000
```

3. Проверка API:

```text
https://ТВОЯ-ССЫЛКА.trycloudflare.com/api/ping
```

Должно показать:

```json
{"message":"API работает"}
```

USB-проброс `adb reverse` при Cloudflare не нужен. Телефон должен быть подключён к интернету.
