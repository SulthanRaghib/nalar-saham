# 📈 Nalar Saham - Indonesian Stock Analysis Application

<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
</p>

<p align="center">
  <strong>Professional Stock Analysis System for Indonesian Stock Exchange (IDX)</strong>
</p>

## 🚀 Features

- **📊 Real-time Stock Analysis** - Fetch live data from Indonesian Stock Exchange
- **🤖 Automated Fundamental Analysis** - Automatic calculation of Graham Number, ROE, DER, NPM
- **💹 Investment Verdict** - Smart recommendations: BUY, HOLD, or AVOID
- **📱 Modern UI** - Built with Livewire 4 + Tailwind CSS v4
- **⚡ Redis Caching** - Optimized performance with intelligent caching
- **🕐 24-hour History** - Track anonymous user analysis history
- **🔄 Auto-Fallback** - Manual mode when API is unavailable

## 🛠️ Tech Stack

- **Framework:** Laravel 12
- **Frontend:** Livewire 4 + Tailwind CSS v4
- **Cache/Session:** Redis
- **Database:** SQLite (configurable)
- **Stock API:** GOAPI.id (Indonesian Stock Exchange)

## 📋 Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js & NPM
- Redis Server
- GOAPI.id API Key ([Register here](https://goapi.id/))

## 🔧 Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd nalar-saham
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure GOAPI.id

Register at [https://goapi.id/](https://goapi.id/) and get your API key.

Update `.env`:

```env
GOAPI_API_KEY=your_actual_api_key_here
GOAPI_BASE_URL=https://api.goapi.id

CACHE_STORE=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

📚 **Detailed Setup Guide:** See [GOAPI_SETUP.md](GOAPI_SETUP.md) for complete configuration instructions.

### 5. Database Setup

```bash
php artisan migrate
```

### 6. Build Assets

```bash
npm run build
```

### 7. Start Development Server

```bash
php artisan serve
```

Visit: `http://localhost:8000`

## 🧪 Testing

### Test GOAPI Integration

```bash
php artisan tinker
```

```php
$service = app(\App\Services\StockApiService::class);
$data = $service->fetchFundamentalData('BBCA');
dd($data);
```

### Supported Indonesian Tickers

- `BBCA` - Bank Central Asia
- `BBRI` - Bank Rakyat Indonesia
- `TLKM` - Telkom Indonesia
- `ASII` - Astra International
- `UNVR` - Unilever Indonesia

## 📊 How It Works

### 1. **Stock Data Fetching**

- Connects to GOAPI.id API
- Fetches real-time price and fundamental data
- Caches results for 5 minutes

### 2. **Graham Number Calculation**

Uses Benjamin Graham's formula:

```
Fair Value = √(22.5 × EPS × BVPS)
```

### 3. **Health Score Analysis**

Evaluates three key metrics:

- **ROE (Return on Equity)** - Above 15% = Good
- **DER (Debt to Equity Ratio)** - Below 1.0 = Good
- **NPM (Net Profit Margin)** - Above 10% = Good

### 4. **Investment Verdict**

- **BUY** - Undervalued with good fundamentals
- **HOLD** - Fairly valued
- **AVOID** - Overvalued or poor fundamentals

## 🏗️ Architecture

```
app/
├── Livewire/
│   └── StockAnalyzer.php          # Main Livewire component
├── Services/
│   ├── StockApiService.php        # GOAPI integration
│   ├── ValuationEngine.php        # Graham Number calculator
│   ├── HealthScorer.php           # Fundamental analysis
│   └── AnalysisHistoryService.php # Redis history management
└── Http/
    └── Middleware/
        └── AssignGuestId.php      # Anonymous user tracking
```

## 🔐 Security

- API keys stored securely in `.env`
- Input sanitization and validation
- CSRF protection enabled
- XSS prevention through Blade templating

## 🚦 API Rate Limits

**GOAPI.id Free Tier:**

- 100 requests per day
- Automatic caching reduces API calls by 90%+
- Graceful fallback to manual mode

## 🐛 Troubleshooting

### API Not Working?

1. Check `.env` has valid `GOAPI_API_KEY`
2. Run `php artisan config:clear`
3. Check logs: `storage/logs/laravel.log`
4. Verify API key at [GOAPI Dashboard](https://goapi.id/)

### Cache Issues?

```bash
php artisan cache:clear
php artisan config:clear
```

### Redis Connection Failed?

Ensure Redis is running:

```bash
redis-cli ping
# Should return: PONG
```

## 📖 Documentation

- [GOAPI Setup Guide](GOAPI_SETUP.md) - Complete API configuration
- [Laravel Documentation](https://laravel.com/docs) - Framework reference
- [Livewire Documentation](https://livewire.laravel.com) - Component guide

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch
3. Follow PSR-12 coding standards
4. Add tests for new features
5. Submit a pull request

## 📝 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 👥 Credits

- Built with [Laravel 12](https://laravel.com)
- Stock data provided by [GOAPI.id](https://goapi.id/)
- Analysis methodology based on Benjamin Graham's Value Investing principles

---

**Made with ❤️ for Indonesian Investors**
