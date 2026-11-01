<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>UC Color Run - Registration</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="{{ asset('css/registration.css') }}">
</head>
<body>

  <div class="registration-wrapper">

    <!-- Back to home nav -->
    <div class="back-nav">
      <a href="{{ url('/') }}" class="back-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <line x1="19" y1="12" x2="5" y2="12"></line>
          <polyline points="12 19 5 12 12 5"></polyline>
        </svg>
        Back to home
      </a>
    </div>

    <!-- MAIN REGISTRATION CARD -->
    <div class="reg-card">
      
      <!-- Card Header -->
      <div class="reg-header">
        <div class="reg-logo">LOGO</div>
        <div class="reg-subtitle">You are registering with UC Color Run</div>
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

      <form action="{{ route('registration.store') }}" method="POST" id="registrationForm">
        @csrf

        <!-- 1. PERSONAL INFORMATION -->
        <div class="form-section">
          <div class="section-title">1. PERSONAL INFORMATION</div>

          <div class="form-grid">
            <!-- Full Name -->
            <div class="form-group">
              <label class="form-label" for="full_name">Full Name <span class="req">*</span></label>
              <input type="text" 
                     id="full_name" 
                     name="full_name" 
                     class="form-control" 
                     placeholder="Enter Full name" 
                     value="{{ old('full_name', auth()->user()->name ?? '') }}" 
                     required>
              @error('full_name')
                <div class="field-error">{{ $message }}</div>
              @enderror
            </div>

            <!-- Email Address -->
            <div class="form-group">
              <label class="form-label" for="email">Email Address <span class="req">*</span></label>
              <input type="email" 
                     id="email" 
                     name="email" 
                     class="form-control" 
                     placeholder="you@example.com (Autofill)" 
                     value="{{ old('email', auth()->user()->email ?? '') }}" 
                     required>
              @error('email')
                <div class="field-error">{{ $message }}</div>
              @enderror
            </div>

            <!-- Phone Number -->
            <div class="form-group">
              <label class="form-label" for="phone_number">Phone Number <span class="req">*</span></label>
              <input type="tel" 
                     id="phone_number" 
                     name="phone_number" 
                     class="form-control" 
                     placeholder="08xxxxxxxxxx" 
                     value="{{ old('phone_number') }}" 
                     required>
              @error('phone_number')
                <div class="field-error">{{ $message }}</div>
              @enderror
            </div>

            <!-- Date of Birth -->
            <div class="form-group">
              <label class="form-label" for="date_of_birth">Date of Birth <span class="req">*</span></label>
              <input type="date" 
                     id="date_of_birth" 
                     name="date_of_birth" 
                     class="form-control" 
                     placeholder="DD/MM/YYYY" 
                     value="{{ old('date_of_birth') }}" 
                     required>
              @error('date_of_birth')
                <div class="field-error">{{ $message }}</div>
              @enderror
            </div>

            <!-- Gender -->
            <div class="form-group">
              <label class="form-label" for="gender">Gender <span class="req">*</span></label>
              <select id="gender" name="gender" class="form-control" required>
                <option value="" disabled {{ old('gender') ? '' : 'selected' }}>Select gender</option>
                <option value="Male" {{ old('gender') === 'Male' ? 'selected' : '' }}>Male</option>
                <option value="Female" {{ old('gender') === 'Female' ? 'selected' : '' }}>Female</option>
              </select>
              @error('gender')
                <div class="field-error">{{ $message }}</div>
              @enderror
            </div>

            <!-- KTP / Passport -->
            <div class="form-group">
              <label class="form-label" for="ktp_passport">KTP/Passport <span class="req">*</span></label>
              <input type="text" 
                     id="ktp_passport" 
                     name="ktp_passport" 
                     class="form-control" 
                     placeholder="Enter KTP/Passport ID" 
                     value="{{ old('ktp_passport') }}" 
                     required>
              @error('ktp_passport')
                <div class="field-error">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <!-- 2. RACE INFORMATION -->
        <div class="form-section">
          <div class="section-title">2. RACE INFORMATION</div>

          <div class="form-grid">
            <!-- Race Category -->
            <div class="form-group">
              <label class="form-label" for="race_category">Race Category <span class="req">*</span></label>
              <select id="race_category" name="race_category" class="form-control" required>
                <option value="" disabled {{ old('race_category') ? '' : 'selected' }}>Select race category</option>
                <option value="5K Run" {{ old('race_category') === '5K Run' ? 'selected' : '' }}>5K Run (Rp150.000)</option>
                <option value="10K Run" {{ old('race_category') === '10K Run' ? 'selected' : '' }}>10K Run (Rp200.000)</option>
                <option value="21K Half Marathon" {{ old('race_category') === '21K Half Marathon' ? 'selected' : '' }}>21K Half Marathon (Rp300.000)</option>
              </select>
              @error('race_category')
                <div class="field-error">{{ $message }}</div>
              @enderror
            </div>

            <!-- T-shirt Size -->
            <div class="form-group">
              <label class="form-label" for="tshirt_size">T-shirt Size <span class="req">*</span></label>
              <select id="tshirt_size" name="tshirt_size" class="form-control" required>
                <option value="" disabled {{ old('tshirt_size') ? '' : 'selected' }}>Select t-shirt size</option>
                <option value="XS" {{ old('tshirt_size') === 'XS' ? 'selected' : '' }}>XS</option>
                <option value="S" {{ old('tshirt_size') === 'S' ? 'selected' : '' }}>S</option>
                <option value="M" {{ old('tshirt_size') === 'M' ? 'selected' : '' }}>M</option>
                <option value="L" {{ old('tshirt_size') === 'L' ? 'selected' : '' }}>L</option>
                <option value="XL" {{ old('tshirt_size') === 'XL' ? 'selected' : '' }}>XL</option>
                <option value="XXL" {{ old('tshirt_size') === 'XXL' ? 'selected' : '' }}>XXL</option>
              </select>
              @error('tshirt_size')
                <div class="field-error">{{ $message }}</div>
              @enderror
            </div>

            <!-- Emergency Contact Name -->
            <div class="form-group">
              <label class="form-label" for="emergency_name">Emergency Contact Name <span class="req">*</span></label>
              <input type="text" 
                     id="emergency_name" 
                     name="emergency_name" 
                     class="form-control" 
                     placeholder="Enter Full name" 
                     value="{{ old('emergency_name') }}" 
                     required>
              @error('emergency_name')
                <div class="field-error">{{ $message }}</div>
              @enderror
            </div>

            <!-- Emergency Contact Phone -->
            <div class="form-group">
              <label class="form-label" for="emergency_phone">Emergency Contact Phone <span class="req">*</span></label>
              <input type="tel" 
                     id="emergency_phone" 
                     name="emergency_phone" 
                     class="form-control" 
                     placeholder="08xxxxxxxxxx" 
                     value="{{ old('emergency_phone') }}" 
                     required>
              @error('emergency_phone')
                <div class="field-error">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <!-- 3. ADDRESS INFORMATION -->
        <div class="form-section">
          <div class="section-title">3. ADDRESS INFORMATION</div>

          <div class="form-grid">
            <!-- Province -->
            <div class="form-group">
              <label class="form-label" for="province">Province <span class="req">*</span></label>
              <select id="province" name="province" class="form-control" required onchange="handleProvinceChange(this.value)">
                <option value="" disabled {{ old('province') ? '' : 'selected' }}>Select province</option>
                <option value="Jawa Timur" {{ old('province') === 'Jawa Timur' ? 'selected' : '' }}>Jawa Timur</option>
                <option value="DKI Jakarta" {{ old('province') === 'DKI Jakarta' ? 'selected' : '' }}>DKI Jakarta</option>
                <option value="Jawa Barat" {{ old('province') === 'Jawa Barat' ? 'selected' : '' }}>Jawa Barat</option>
                <option value="Jawa Tengah" {{ old('province') === 'Jawa Tengah' ? 'selected' : '' }}>Jawa Tengah</option>
                <option value="DI Yogyakarta" {{ old('province') === 'DI Yogyakarta' ? 'selected' : '' }}>DI Yogyakarta</option>
                <option value="Banten" {{ old('province') === 'Banten' ? 'selected' : '' }}>Banten</option>
                <option value="Bali" {{ old('province') === 'Bali' ? 'selected' : '' }}>Bali</option>
                <option value="Sumatera Utara" {{ old('province') === 'Sumatera Utara' ? 'selected' : '' }}>Sumatera Utara</option>
                <option value="Lainnya" {{ old('province') === 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
              </select>
              @error('province')
                <div class="field-error">{{ $message }}</div>
              @enderror
            </div>

            <!-- City -->
            <div class="form-group">
              <label class="form-label" for="city">City <span class="req">*</span></label>
              <select id="city" name="city" class="form-control" required>
                <option value="" disabled {{ old('city') ? '' : 'selected' }}>Select city</option>
                @if(old('city'))
                  <option value="{{ old('city') }}" selected>{{ old('city') }}</option>
                @endif
              </select>
              @error('city')
                <div class="field-error">{{ $message }}</div>
              @enderror
            </div>

            <!-- Full Address -->
            <div class="form-group full-width">
              <label class="form-label" for="address">Full Address <span class="req">*</span></label>
              <textarea id="address" 
                        name="address" 
                        class="form-control" 
                        placeholder="Enter Full address" 
                        rows="2" 
                        required>{{ old('address') }}</textarea>
              @error('address')
                <div class="field-error">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <!-- Footnote -->
        <div class="form-footnote">
          * Field wajib diisi. Email diisi otomatis setelah peserta login dengan Google.
        </div>

        <hr class="reg-divider" style="margin-top: 10px; margin-bottom: 18px;">

        <!-- Agreement Checkbox -->
        <div class="agreement-wrap">
          <input type="checkbox" id="agreement" name="agreement" class="agreement-checkbox" value="1" {{ old('agreement') ? 'checked' : '' }} required>
          <label for="agreement" class="agreement-label">
            I agree to the <a href="{{ url('/regulation') }}" target="_blank">Race Regulations</a> and <a href="#" target="_blank">Privacy Policy</a>.
          </label>
        </div>
        @error('agreement')
          <div class="field-error" style="margin-top: -14px; margin-bottom: 14px;">{{ $message }}</div>
        @enderror

        <!-- Submit Button -->
        <button type="submit" class="btn-submit-reg" id="btnSubmit">
          Submit Registration
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
      citySelect.innerHTML = '<option value="" disabled selected>Select city</option>';

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

    // Initialize city if old value was present
    window.addEventListener('DOMContentLoaded', () => {
      const initialProvince = document.getElementById('province').value;
      const oldCity = "{{ old('city') }}";
      if (initialProvince) {
        handleProvinceChange(initialProvince, oldCity);
      }
    });
  </script>
</body>
</html>

