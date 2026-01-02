# İleti Merkezi SMS 2FA - WordPress Plugin

WordPress SMS eklentisi - İleti Merkezi API entegrasyonu ile 2 faktörlü kimlik doğrulama (2FA)

## Özellikler

- İleti Merkezi API entegrasyonu ile SMS gönderimi
- WordPress giriş sürecine entegre 2 faktörlü kimlik doğrulama (2FA)
- Kullanıcı girişinde otomatik OTP (One-Time Password) gönderimi
- Yönetici panelinde API kimlik bilgileri ayarlama
- Kullanıcı profilinde telefon numarası yönetimi
- OTP kodlarının güvenli saklanması ve süre sonu yönetimi
- Test SMS gönderme özelliği

## Kurulum

1. Plugin dosyalarını WordPress'in `wp-content/plugins/ileti-merkezi-sms/` dizinine yükleyin
2. WordPress yönetici panelinden eklentiyi etkinleştirin
3. Ayarlar > İleti Merkezi SMS menüsünden API bilgilerinizi girin:
   - API Kullanıcı Adı
   - API Şifre
   - Gönderici Başlığı

## Yapılandırma

### API Ayarları
1. WordPress yönetici paneline giriş yapın
2. **Ayarlar > İleti Merkezi SMS** menüsüne gidin
3. API kimlik bilgilerinizi girin:
   - **API Username**: İleti Merkezi kullanıcı adınız
   - **API Password**: İleti Merkezi şifreniz
   - **Sender Title**: SMS'lerde görünecek gönderici adı

### 2FA Ayarları
- **Enable 2FA**: 2 faktörlü kimlik doğrulamayı etkinleştir/devre dışı bırak
- **OTP Length**: Doğrulama kodu uzunluğu (4-8 rakam)
- **OTP Expiry**: Doğrulama kodunun geçerlilik süresi (dakika)

### Kullanıcı Telefon Numarası
Her kullanıcının 2FA kullanabilmesi için telefon numarası eklemesi gerekir:
1. **Kullanıcılar > Profil** menüsüne gidin
2. "SMS 2FA Settings" bölümünde telefon numaranızı girin
3. Format: +90XXXXXXXXXX

## Kullanım

### 2FA ile Giriş
1. WordPress giriş sayfasında kullanıcı adı ve şifrenizi girin
2. Şifreniz doğruysa, telefon numaranıza bir doğrulama kodu gönderilir
3. Gelen doğrulama kodunu giriş sayfasındaki alana girin
4. "Verify Code" butonuna tıklayarak girişi tamamlayın

### Test SMS Gönderme
1. **Ayarlar > İleti Merkezi SMS** menüsüne gidin
2. Sayfanın altındaki "Test SMS" bölümünde bir telefon numarası girin
3. "Send Test SMS" butonuna tıklayın
4. SMS'in başarıyla gönderilip gönderilmediğini kontrol edin

## Güvenlik

- Tüm kullanıcı girişleri sanitize edilir ve validate edilir
- OTP kodları veritabanında güvenli şekilde saklanır
- Süresi dolan OTP kodları otomatik olarak silinir
- Nonce kontrolü ile CSRF koruması sağlanır
- API kimlik bilgileri WordPress options tablosunda saklanır

## Teknik Detaylar

### Veritabanı Tablosu
Plugin, OTP kodlarını saklamak için `wp_ileti_merkezi_otp` tablosu oluşturur:
- `id`: Benzersiz ID
- `user_id`: WordPress kullanıcı ID
- `otp_code`: Doğrulama kodu
- `phone_number`: Telefon numarası
- `created_at`: Oluşturulma zamanı
- `expires_at`: Son kullanma zamanı
- `is_used`: Kullanılma durumu

### API Entegrasyonu
Plugin, İleti Merkezi API'si ile XML formatında iletişim kurar:
- API URL: https://api.iletimerkezi.com/v1/send-sms
- Format: XML
- Kimlik doğrulama: Username/Password

### Dosya Yapısı
```
ileti-merkezi-sms/
├── ileti-merkezi-sms.php          # Ana plugin dosyası
├── includes/
│   ├── class-ileti-merkezi-api.php    # API entegrasyonu
│   ├── class-ileti-merkezi-otp.php    # OTP yönetimi
│   ├── class-ileti-merkezi-admin.php  # Yönetici ayarları
│   └── class-ileti-merkezi-auth.php   # Kimlik doğrulama
├── assets/
│   ├── css/                           # CSS dosyaları
│   └── js/                            # JavaScript dosyaları
└── README.md                          # Dokümantasyon
```

## Gereksinimler

- WordPress 5.0 veya üzeri
- PHP 7.2 veya üzeri
- İleti Merkezi API hesabı
- SimpleXML PHP eklentisi

## Lisans

GPL v2 or later

## Destek

Sorunlar ve öneriler için: https://github.com/integrumart/wordpressiletimerkeziSMS/issues
