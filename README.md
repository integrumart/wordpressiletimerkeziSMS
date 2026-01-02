# İleti Merkezi SMS 2FA WordPress Plugin

WordPress SMS eklentisi - İleti Merkezi API ile 2 faktörlü doğrulama (2FA)

## Özellikler

- **İleti Merkezi API Entegrasyonu**: İleti Merkezi SMS servisi ile tam entegrasyon
- **2 Faktörlü Doğrulama (2FA)**: WordPress girişine SMS ile OTP doğrulama
- **Güvenli OTP Sistemi**: Hash'lenmiş OTP saklama ve otomatik süre dolumu
- **Detaylı Ayarlar**: API kimlik bilgileri ve 2FA ayarları için kolay yönetim paneli
- **Hata Yönetimi**: Kapsamlı hata kontrolü ve kullanıcı dostu mesajlar
- **WordPress Standartları**: WordPress kodlama standartlarına uygun geliştirme

## Kurulum

1. Bu repository'yi WordPress eklentiler dizinine klonlayın veya indirin:
   ```bash
   cd wp-content/plugins/
   git clone https://github.com/integrumart/wordpressiletimerkeziSMS.git ileti-merkezi-sms
   ```

2. WordPress yönetim panelinde **Eklentiler** > **Yüklü Eklentiler** sayfasına gidin

3. "İleti Merkezi SMS 2FA" eklentisini aktifleştirin

4. **Ayarlar** > **İleti Merkezi SMS** sayfasından API bilgilerinizi yapılandırın

## Yapılandırma

### İleti Merkezi API Ayarları

1. WordPress yönetim panelinde **Ayarlar** > **İleti Merkezi SMS** menüsüne gidin
2. Aşağıdaki bilgileri girin:
   - **API Username**: İleti Merkezi API kullanıcı adınız
   - **API Password**: İleti Merkezi API şifreniz
   - **Sender Title**: SMS gönderici başlığınız

### 2FA Ayarları

- **Enable 2FA**: 2 faktörlü doğrulamayı etkinleştir/devre dışı bırak
- **OTP Length**: OTP kodu uzunluğu (4-10 karakter arası)
- **OTP Expiry**: OTP kodunun geçerlilik süresi (saniye cinsinden, minimum 60)

### API Bağlantı Testi

Ayarlar sayfasının altındaki "Test Connection" butonu ile API bağlantınızı test edebilirsiniz.

## Kullanım

### 2FA ile Giriş

1. Kullanıcılar normal şekilde giriş yapmaya çalıştığında (kullanıcı adı ve şifre ile)
2. Kimlik bilgileri doğruysa, telefon numaralarına SMS ile OTP kodu gönderilir
3. Giriş sayfasında OTP kodu girme alanı görünür
4. Kullanıcı SMS ile gelen kodu girer
5. Kod doğruysa giriş tamamlanır

### Telefon Numarası Gereksinimleri

Plugin, kullanıcının telefon numarasını şu meta alanlardan arar:
- `billing_phone` (WooCommerce kullanıcıları için)
- `phone` (genel kullanım için)

Kullanıcıların profillerinde telefon numarası olması gerekir.

## Güvenlik Özellikleri

- **Hash'lenmiş OTP Saklama**: OTP kodları hash'lenerek saklanır
- **Deneme Sınırı**: Maksimum 3 yanlış deneme hakkı
- **Otomatik Süre Dolumu**: OTP kodları belirtilen süre sonunda otomatik silinir
- **Nonce Koruması**: Form güvenliği için nonce kullanımı
- **Sanitizasyon ve Escape**: Tüm kullanıcı girdileri sanitize edilir ve çıktılar escape edilir

## Geliştirme

### Dosya Yapısı

```
ileti-merkezi-sms/
├── ileti-merkezi-sms.php          # Ana eklenti dosyası
├── includes/
│   ├── class-ileti-merkezi-api.php # İleti Merkezi API client
│   ├── class-otp-manager.php       # OTP yönetim sınıfı
│   ├── class-settings.php          # Ayarlar sayfası
│   └── class-login-handler.php     # Giriş işleyici
├── assets/
│   ├── css/
│   │   ├── admin.css               # Yönetim paneli stilleri
│   │   └── login.css               # Giriş sayfası stilleri
│   └── js/                         # JavaScript dosyaları
└── languages/                      # Çeviri dosyaları

```

### Hooks ve Filters

Plugin aşağıdaki WordPress hook'larını kullanır:

- `authenticate`: Giriş işlemini yakalar ve 2FA ekler
- `login_form`: OTP doğrulama formunu render eder
- `login_enqueue_scripts`: Giriş sayfası stil dosyalarını yükler

## Sistem Gereksinimleri

- WordPress 5.0 veya üstü
- PHP 7.2 veya üstü
- İleti Merkezi API hesabı

## Lisans

GPL v2 veya üstü - Detaylar için LICENSE dosyasına bakın

## Destek

Sorun bildirmek veya özellik isteğinde bulunmak için GitHub Issues kullanın:
https://github.com/integrumart/wordpressiletimerkeziSMS/issues

## Katkıda Bulunma

Pull request'ler memnuniyetle karşılanır. Büyük değişiklikler için lütfen önce bir issue açarak neyi değiştirmek istediğinizi tartışın.

## Yazarlar

- Integrumart - https://github.com/integrumart

## Teşekkürler

İleti Merkezi SMS servisi için: https://www.iletimerkezi.com/
