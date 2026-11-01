<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>UC Color Run - Edit Registration</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="{{ asset('css/registration.css') }}">
</head>
<body>

  <div class="registration-wrapper">

    <div class="back-nav">
      <a href="{{ route('my-registration') }}" class="back-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="19" y1="12" x2="5" y2="12"></line>
          <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Back to My Registration
      </a>
    </div>

    <div class="reg-card">
      <div class="reg-header">
        <div class="reg-logo">LOGO</div>
        <div class="reg-subtitle">Edit Registration Details ({{ $registration->registration_code }})</div>
      </div>

      <hr class="reg-divider">

      @if ($errors->any())
        <div style="background: #fee2e2; border: 1px solid #f87171; color: #991b1b; padding: 12px 16px; border-radius: 10px; font-size: 13px; margin-bottom: 20px;">
          <strong>Mohon periksa kembali formulir:</strong>
          <ul style="margin: 6px 0 0 18px;">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form action="{{ route('registration.update', $registration->id) }}" method="POST">
        @csrf
        @method('PUT')

        <!-- 1. PERSONAL INFORMATION -->
        <div class="form-section">
          <div class="section-title">1. PERSONAL INFORMATION</div>

          <div class="form-grid">
            <div class="form-group">
              <label class="form-label" for="full_name">Full Name <span class="req">*</span></label>
              <input type="text" id="full_name" name="full_name" class="form-control" value="{{ old('full_name', $registration->full_name) }}" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="email">Email Address <span class="req">*</span></label>
              <input type="email" id="email" name="email" class="form-control" value="{{ old('email', $registration->email) }}" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="phone_number">Phone Number <span class="req">*</span></label>
              <input type="tel" id="phone_number" name="phone_number" class="form-control" value="{{ old('phone_number', $registration->phone_number) }}" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="date_of_birth">Date of Birth <span class="req">*</span></label>
              <input type="date" id="date_of_birth" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $registration->date_of_birth?->format('Y-m-d')) }}" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="gender">Gender <span class="req">*</span></label>
              <select id="gender" name="gender" class="form-control" required>
                <option value="Male" {{ old('gender', $registration->gender) === 'Male' ? 'selected' : '' }}>Male</option>
                <option value="Female" {{ old('gender', $registration->gender) === 'Female' ? 'selected' : '' }}>Female</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label" for="ktp_passport">KTP/Passport <span class="req">*</span></label>
              <input type="text" id="ktp_passport" name="ktp_passport" class="form-control" value="{{ old('ktp_passport', $registration->ktp_passport) }}" required>
            </div>
          </div>
        </div>

        <!-- 2. RACE INFORMATION -->
        <div class="form-section">
          <div class="section-title">2. RACE INFORMATION</div>

          <div class="form-grid">
            <div class="form-group">
              <label class="form-label" for="race_category">Race Category <span class="req">*</span></label>
              <select id="race_category" name="race_category" class="form-control" required>
                <option value="5K Run" {{ old('race_category', $registration->race_category) === '5K Run' ? 'selected' : '' }}>5K Run (Rp150.000)</option>
                <option value="10K Run" {{ old('race_category', $registration->race_category) === '10K Run' ? 'selected' : '' }}>10K Run (Rp200.000)</option>
                <option value="21K Half Marathon" {{ old('race_category', $registration->race_category) === '21K Half Marathon' ? 'selected' : '' }}>21K Half Marathon (Rp300.000)</option>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label" for="tshirt_size">T-shirt Size <span class="req">*</span></label>
              <select id="tshirt_size" name="tshirt_size" class="form-control" required>
                @foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL'] as $size)
                  <option value="{{ $size }}" {{ old('tshirt_size', $registration->tshirt_size) === $size ? 'selected' : '' }}>{{ $size }}</option>
                @endforeach
              </select>
            </div>

            <div class="form-group">
              <label class="form-label" for="emergency_name">Emergency Contact Name <span class="req">*</span></label>
              <input type="text" id="emergency_name" name="emergency_name" class="form-control" value="{{ old('emergency_name', $registration->emergency_name) }}" required>
            </div>

            <div class="form-group">
              <label class="form-label" for="emergency_phone">Emergency Contact Phone <span class="req">*</span></label>
              <input type="tel" id="emergency_phone" name="emergency_phone" class="form-control" value="{{ old('emergency_phone', $registration->emergency_phone) }}" required>
            </div>
          </div>
        </div>

        <!-- 3. ADDRESS INFORMATION -->
        <div class="form-section">
          <div class="section-title">3. ADDRESS INFORMATION</div>

          <div class="form-grid">
            <div class="form-group">
              <label class="form-label" for="province">Province <span class="req">*</span></label>
              <select id="province" name="province" class="form-control" required onchange="handleProvinceChange(this.value)">
                @foreach (['Jawa Timur', 'DKI Jakarta', 'Jawa Barat', 'Jawa Tengah', 'DI Yogyakarta', 'Banten', 'Bali', 'Sumatera Utara', 'Lainnya'] as $prov)
                  <option value="{{ $prov }}" {{ old('province', $registration->province) === $prov ? 'selected' : '' }}>{{ $prov }}</option>
                @endforeach
              </select>
            </div>

            <div class="form-group">
              <label class="form-label" for="city">City <span class="req">*</span></label>
              <select id="city" name="city" class="form-control" required>
                <option value="{{ old('city', $registration->city) }}" selected>{{ old('city', $registration->city) }}</option>
              </select>
            </div>

            <div class="form-group full-width">
              <label class="form-label" for="address">Full Address <span class="req">*</span></label>
              <textarea id="address" name="address" class="form-control" rows="2" required>{{ old('address', $registration->address) }}</textarea>
            </div>
          </div>
        </div>

        <button type="submit" class="btn-submit-reg">
          Save Changes
        </button>
      </form>
    </div>
  </div>

  <script>
    const cityMap = {
      'Jawa Timur': ['Surabaya', 'Sidoarjo', 'Malang', 'Gresik', 'Batu', 'Pasuruan', 'Mojokerto', 'Kediri', 'Jember', 'Banyuwangi'],
      'DKI Jakarta': ['Jakarta Pusat', 'Jakarta Selatan', 'Jakarta Barat', 'Jakarta Timur', 'Jakarta Utara', 'Kepulauan Seribu'],
      'Jawa Barat': ['Bandung', 'Bekasi', 'Bogor', 'Depok', 'Cimahi', 'Cirebon', 'Sukabumi', 'Tasikmalaya'],
      'Jawa Tengah': ['Semarang', 'Surakarta (Solo)', 'Magelang', 'Pekalongan', 'Salatiga', 'Tegal', 'Purwokerto'],
      'DI Yogyakarta': ['Yogyakarta', 'Sleman', 'Bantul', 'Kulon Progo', 'Gunungkidul'],
      'Banten': ['Tangerang', 'Tangerang Selatan', 'Serang', 'Cilegon'],
      'Bali': ['Denpasar', 'Badung', 'Gianyar', 'Tabanan', 'Buleleng'],
      'Sumatera Utara': ['Medan', 'Binjai', 'Pematangsiantar', 'Tebing Tinggi', 'Deli Serdang'],
      'Lainnya': ['Kota Lainnya']
    };

    function handleProvinceChange(province, selectedCity = null) {
      const citySelect = document.getElementById('city');
      citySelect.innerHTML = '';

      const cities = cityMap[province] || ['Kota Lainnya'];
      cities.forEach(city => {
        const opt = document.createElement('option');
        opt.value = city;
        opt.textContent = city;
        if (selectedCity && selectedCity === city) {
          opt.selected = true;
        }
        citySelect.appendChild(opt);
      });
    }

    window.addEventListener('DOMContentLoaded', () => {
      const currentProv = document.getElementById('province').value;
      const currentCity = "{{ old('city', $registration->city) }}";
      if (currentProv) {
        handleProvinceChange(currentProv, currentCity);
      }
    });
  </script>
</body>
</html>

