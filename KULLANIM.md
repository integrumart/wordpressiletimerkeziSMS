# İleti Merkezi SMS Plugin - Kullanım Örnekleri

## İçindekiler
1. [Kurulum Adımları](#kurulum-adımları)
2. [API Yapılandırması](#api-yapılandırması)
3. [Kullanıcı Ayarları](#kullanıcı-ayarları)
4. [2FA Giriş Süreci](#2fa-giriş-süreci)
5. [Sorun Giderme](#sorun-giderme)

## Kurulum Adımları

### 1. Plugin Dosyalarını Yükleme

**Manuel Yükleme:**
1. Tüm plugin dosyalarını bir ZIP dosyasına sıkıştırın
2. WordPress yönetici paneline gidin
3. **Eklentiler > Yeni Ekle** menüsüne tıklayın
4. **Eklenti Yükle** butonuna tıklayın
5. ZIP dosyasını seçin ve yükleyin
6. **Eklentiyi Etkinleştir** butonuna tıklayın

**FTP ile Yükleme:**
1. Plugin dosyalarını FTP ile `/wp-content/plugins/ileti-merkezi-sms/` dizinine yükleyin
2. WordPress yönetici paneline gidin
3. **Eklentiler** menüsünden "İleti Merkezi SMS 2FA" eklentisini bulun
4. **Etkinleştir** linkine tıklayın

### 2. Plugin Etkinleştirme

Plugin etkinleştirildiğinde otomatik olarak:
- `wp_ileti_merkezi_otp` veritabanı tablosu oluşturulur
- Varsayılan ayarlar yapılandırılır:
  - 2FA: Etkin
  - OTP Uzunluğu: 6 rakam
  - OTP Geçerlilik Süresi: 5 dakika

## API Yapılandırması

### Adım 1: İleti Merkezi API Bilgilerini Alma

1. [İleti Merkezi](https://www.iletimerkezi.com/) web sitesine gidin
2. Hesabınıza giriş yapın
3. API kimlik bilgilerinizi not edin:
   - Kullanıcı Adı
   - Şifre
   - Gönderici Başlığı (onaylı sender ID)

### Adım 2: WordPress'te API Ayarları

1. WordPress yönetici paneline giriş yapın
2. **Ayarlar > İleti Merkezi SMS** menüsüne gidin
3. Aşağıdaki bilgileri girin:

```
API Username: [ileti-merkezi-kullanici-adi]
API Password: [ileti-merkezi-sifre]
Sender Title: [onaylanmis-gonderici-adi]
```

4. **Ayarları Kaydet** butonuna tıklayın

### Adım 3: API Bağlantısını Test Etme

1. Ayarlar sayfasının altındaki "Test SMS" bölümüne gidin
2. Test telefon numaranızı girin (örn: +905551234567)
3. **Send Test SMS** butonuna tıklayın
4. Başarılı mesajı ve SMS'i kontrol edin

**Örnek Test Çıktısı:**
```
✓ SMS başarıyla gönderildi
```

## Kullanıcı Ayarları

### Telefon Numarası Ekleme

Her kullanıcının 2FA kullanabilmesi için telefon numarası eklemesi gerekir.

#### Kendi Profiliniz için:
1. WordPress yönetici paneline giriş yapın
2. **Kullanıcılar > Profil** menüsüne gidin
3. "SMS 2FA Settings" bölümünü bulun
4. Telefon numaranızı girin:
   - Format: +90XXXXXXXXXX
   - Örnek: +905551234567
5. **Profili Güncelle** butonuna tıklayın

#### Diğer Kullanıcılar için (Yöneticiler):
1. **Kullanıcılar > Tüm Kullanıcılar** menüsüne gidin
2. İlgili kullanıcının **Düzenle** linkine tıklayın
3. "SMS 2FA Settings" bölümünü bulun
4. Telefon numarasını girin
5. **Kullanıcıyı Güncelle** butonuna tıklayın

### 2FA Ayarları

Plugin varsayılan olarak 2FA'yı etkinleştirir, ancak özelleştirebilirsiniz:

```
Enable 2FA: ✓ (İşaretli - Etkin)
OTP Length: 6 (4-8 arası)
OTP Expiry: 5 (dakika)
```

**Önerilen Ayarlar:**
- **Yüksek Güvenlik:** OTP Length: 8, Expiry: 3 dakika
- **Standart Güvenlik:** OTP Length: 6, Expiry: 5 dakika
- **Kullanıcı Dostu:** OTP Length: 4, Expiry: 10 dakika

## 2FA Giriş Süreci

### Normal Kullanıcı Girişi

#### 1. WordPress Giriş Sayfasına Git
```
https://yoursite.com/wp-login.php
```

#### 2. Kullanıcı Adı ve Şifre Gir
```
Kullanıcı Adı: admin
Şifre: ********
```

#### 3. OTP SMS'i Al
- Şifre doğruysa, telefon numaranıza bir SMS gelir
- SMS İçeriği Örneği:
  ```
  Your verification code for My WordPress Site is: 123456. 
  This code will expire in 5 minutes.
  ```

#### 4. Doğrulama Kodunu Gir
- Giriş sayfası otomatik olarak OTP giriş formunu gösterir
- 6 haneli kodu girin (örn: 123456)
- **Verify Code** butonuna tıklayın

#### 5. Giriş Tamamlandı
- Kod doğruysa, WordPress yönetici paneline yönlendirilirsiniz

### OTP Yeniden Gönderme

Eğer SMS gelmezse veya süre dolarsa:

1. OTP giriş formunun altındaki **"Resend verification code"** linkine tıklayın
2. Yeni bir OTP kodu telefonunuza gönderilir
3. Yeni kodu girin

### 2FA'yı Devre Dışı Bırakma (Geçici)

Acil durumlar için 2FA'yı geçici olarak devre dışı bırakabilirsiniz:

1. FTP veya dosya yöneticisi ile sunucuya bağlanın
2. `wp-config.php` dosyasını düzenleyin
3. Şu satırı ekleyin:
```php
define('ILETI_MERKEZI_DISABLE_2FA', true);
```
4. Dosyayı kaydedin
5. Normal şifre ile giriş yapabilirsiniz
6. 2FA'yı tekrar etkinleştirmek için bu satırı silin

## Sorun Giderme

### SMS Gelmiyor

**Çözüm 1: API Bilgilerini Kontrol Edin**
- Ayarlar > İleti Merkezi SMS menüsüne gidin
- API kullanıcı adı ve şifresinin doğru olduğundan emin olun
- Test SMS göndererek bağlantıyı test edin

**Çözüm 2: Telefon Numarası Formatını Kontrol Edin**
- Telefon numarası formatı: +90XXXXXXXXXX
- Başında sıfır olmamalı (yanlış: +905501234567, ✗)
- Doğru format: +905501234567 ✓

**Çözüm 3: İleti Merkezi Bakiyesini Kontrol Edin**
- İleti Merkezi hesabınıza giriş yapın
- Bakiyenizin yeterli olduğundan emin olun

### "Phone number not found" Hatası

Bu hata, kullanıcı profilinde telefon numarası olmadığında görünür.

**Çözüm:**
1. Kullanıcılar > Profil menüsüne gidin
2. "SMS 2FA Settings" bölümüne telefon numaranızı ekleyin
3. Profili güncelleyin

### "Invalid or expired verification code" Hatası

**Sebep 1: Kod Süresi Dolmuş**
- OTP kodları varsayılan olarak 5 dakika geçerlidir
- "Resend verification code" linkine tıklayarak yeni kod alın

**Sebep 2: Kod Yanlış Girilmiş**
- Kodu dikkatli kontrol edin
- Boşluk veya tire olmadan girin

**Sebep 3: Eski Kod Kullanılmış**
- Yeni kod gönderildiğinde eski kodlar geçersiz olur
- En son gelen SMS'teki kodu kullanın

### Plugin Çakışması

Bazı güvenlik veya önbellek eklentileri 2FA ile çakışabilir.

**Test Etme:**
1. Tüm diğer eklentileri geçici olarak devre dışı bırakın
2. Sadece İleti Merkezi SMS eklentisini etkin tutun
3. Giriş yapmayı deneyin
4. Çalışıyorsa, eklentileri tek tek etkinleştirerek sorunu bulun

### Veritabanı Hatası

Nadiren OTP tablosu ile ilgili sorunlar olabilir.

**Çözüm:**
1. Eklentiyi devre dışı bırakın
2. Eklentiyi tekrar etkinleştirin (tablo yeniden oluşturulur)
3. Veya phpMyAdmin'den manuel olarak tabloyu kontrol edin:

```sql
SELECT * FROM wp_ileti_merkezi_otp LIMIT 10;
```

## Güvenlik En İyi Uygulamaları

1. **Güçlü API Şifresi Kullanın**
   - İleti Merkezi API şifrenizi düzenli olarak değiştirin
   - Başkalarıyla paylaşmayın

2. **Telefon Numaralarını Güncel Tutun**
   - Kullanıcıların telefon numaralarını güncel tutmalarını sağlayın
   - Eski numaralar güvenlik riski oluşturur

3. **OTP Süresini Makul Tutun**
   - Çok kısa (1-2 dakika): Kullanıcı deneyimi kötü
   - Çok uzun (15+ dakika): Güvenlik riski
   - Önerilen: 5 dakika

4. **Düzenli Bakım**
   - Eski OTP kayıtları otomatik olarak temizlenir
   - Plugin güncellemelerini takip edin

## Gelişmiş Kullanım

### Programatik SMS Gönderme

Kendi kodunuzdan SMS göndermek için:

```php
<?php
// İleti Merkezi API sınıfını kullan
$api = new Ileti_Merkezi_API();

// SMS gönder
$result = $api->send_sms('+905551234567', 'Test mesajı');

if ($result['success']) {
    echo 'SMS başarıyla gönderildi!';
} else {
    echo 'Hata: ' . $result['message'];
}
?>
```

### OTP Oluşturma ve Doğrulama

```php
<?php
// OTP yöneticisini al
$otp_manager = Ileti_Merkezi_OTP::get_instance();

// Kullanıcı için OTP oluştur ve gönder
$user_id = 1;
$result = $otp_manager->send_otp($user_id);

// OTP doğrula
$is_valid = $otp_manager->verify_otp($user_id, '123456');

if ($is_valid) {
    echo 'OTP doğrulandı!';
} else {
    echo 'Geçersiz OTP!';
}
?>
```

### Kullanıcı Telefon Numarası Yönetimi

```php
<?php
$otp_manager = Ileti_Merkezi_OTP::get_instance();

// Telefon numarasını al
$phone = $otp_manager->get_user_phone($user_id);

// Telefon numarasını ayarla
$otp_manager->set_user_phone($user_id, '+905551234567');
?>
```

## Destek

Sorun, öneri veya katkıda bulunmak için:
- GitHub Issues: https://github.com/integrumart/wordpressiletimerkeziSMS/issues
- Plugin Sayfası: Ayarlar > İleti Merkezi SMS

## Sürüm Geçmişi

**v1.0.0**
- İlk sürüm
- İleti Merkezi API entegrasyonu
- 2FA/2-step verification
- OTP yönetimi
- Admin ayarları sayfası
- Kullanıcı profil entegrasyonu
