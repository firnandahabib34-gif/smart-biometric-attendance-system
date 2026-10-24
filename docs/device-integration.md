# Integrasi perangkat fingerprint

Perangkat ESP32/Arduino dan layanan IoT yang dapat mengirim HTTP POST dapat memakai API berikut. Pastikan perangkat mengirim waktu ke server melalui request saat scan; waktu attendance diambil dari waktu server.

## Pengaturan server

1. Buat token acak panjang dan simpan sebagai `FINGERPRINT_DEVICE_TOKEN` di `.env`.
2. Jalankan `php artisan config:cache` setelah mengubah konfigurasi di server produksi.
3. Gunakan URL HTTPS server dan token yang sama pada konfigurasi perangkat. Jangan masukkan token ke repositori atau tampilkan pada halaman web.
4. Tambahkan sertifikat CA server pada firmware TLS. Jangan menonaktifkan verifikasi sertifikat di jaringan produksi.
5. Jalankan scheduler Laravel setiap menit (`php artisan schedule:run`) melalui task scheduler server agar mahasiswa aktif yang belum tercatat hadir dibuatkan status alpha otomatis setiap hari kerja pukul 23:59.

API memakai header:

```http
Authorization: Bearer <FINGERPRINT_DEVICE_TOKEN>
Content-Type: application/json
Accept: application/json
```

## Scan kehadiran

`POST /api/device/fingerprint/scan`

```json
{
  "fingerprint_id": "template-id-dari-sensor",
  "device_id": "ESP32-KELAS-A"
}
```

Scan pertama pada hari tersebut dicatat sebagai masuk, scan kedua sebagai pulang. Scan berikutnya menghasilkan HTTP `409`; fingerprint yang belum tertaut menghasilkan HTTP `404`. Hasil sukses berisi `event` (`attendance_check_in` atau `attendance_check_out`) dan identitas mahasiswa.

## Enrollment mahasiswa

Buat mahasiswa terlebih dahulu dari modal **Tambah mahasiswa** di halaman Fingerprint & perangkat. Setelah data tersimpan, halaman menampilkan NIM yang perlu didaftarkan dan status **Menunggu pendaftaran perangkat**. Ketika sensor berhasil mendaftarkan sidik jari, perangkat mengirim:

`POST /api/device/fingerprint/enroll`

```json
{
  "student_number": "NIM-MAHASISWA",
  "fingerprint_id": "template-id-dari-sensor",
  "device_id": "ESP32-KELAS-A"
}
```

NIM harus milik mahasiswa aktif. Endpoint akan menautkan ID template, menyimpan waktu enrollment, dan menulis log perangkat. Setelah berhasil, status mahasiswa berubah menjadi **Terdaftar**. ID fingerprint tidak perlu dimasukkan manual lewat form web.

## Contoh pengiriman scan dari ESP32

Contoh ini hanya menangani komunikasi HTTP. Panggil `sendFingerprintScan()` setelah library sensor fingerprint berhasil menemukan template. Library sensor dan proses membaca fingerprint perlu disesuaikan dengan model hardware yang digunakan.

```cpp
#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

const char* WIFI_SSID = "SSID_WIFI";
const char* WIFI_PASSWORD = "PASSWORD_WIFI";
const char* API_URL = "https://domain-anda.example/api/device/fingerprint/scan";
const char* DEVICE_TOKEN = "TOKEN_PERANGKAT";
const char* DEVICE_ID = "ESP32-KELAS-A";
extern const char* ROOT_CA;

bool sendFingerprintScan(const String& fingerprintId) {
    WiFiClientSecure client;
    client.setCACert(ROOT_CA);

    HTTPClient http;
    if (!http.begin(client, API_URL)) {
        return false;
    }

    JsonDocument payload;
    payload["fingerprint_id"] = fingerprintId;
    payload["device_id"] = DEVICE_ID;
    String body;
    serializeJson(payload, body);

    http.addHeader("Authorization", String("Bearer ") + DEVICE_TOKEN);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("Accept", "application/json");
    const int status = http.POST(body);
    const String response = http.getString();
    http.end();

    Serial.printf("HTTP %d: %s\n", status, response.c_str());
    return status == 200;
}
```

Gunakan konfigurasi secure store pada perangkat produksi untuk Wi-Fi dan token; contoh di atas hanya menunjukkan bentuk request, bukan penyimpanan rahasia yang aman.
