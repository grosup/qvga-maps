# Nokia 225 4G Maps

A server-side rendered map application for Nokia 225 4G and other feature phones with 320x240 QVGA screens.

![gmap](https://github.com/user-attachments/assets/291b7b25-37dc-45d6-8399-2f46d5bb6157)


## ✨ Features

- 🗺️ **Static map display** (Mapbox Static Images API)
- 🏷️ **Dynamic titles** with reverse geocoding (shows location name from coordinates)
- 🎨 **Multiple map styles** (Streets, Outdoors, Satellite) with clickable link selector
- 🔍 **Address search** (Nominatim / OpenStreetMap)
- 🧭 **Pan controls** (Up/Down/Left/Right)
- 🔍 **Zoom controls** (+/-)
- 📍 **Session-based state** (coordinates, zoom, map style)
- 💾 **Image caching** (1-hour cache for map tiles and 1-hour for geocoding results)
- 📱 **Optimized for 320x240 QVGA screens**
- 🏗️ **Clean OOP architecture** (MapSession, MapRenderer, MapView)

## 📦 File Structure

```
/
├── index.php                          # Main map interface
├── render_map.php                     # Map tile renderer
├── controller/
│   ├── controller.php                 # Navigation controls handler
│   ├── mapstyle.php                   # Map style change handler
│   └── search.php                     # Address search handler
├── class/
│   ├── MapSession.php                 # Session manager (coordinates, zoom, style)
│   ├── MapRenderer.php                # Mapbox API client & geocoding
│   ├── MapView.php                    # HTML view renderer
│   └── MapController.php              # Navigation logic
├── tools/
│   └── requirements-check.php         # Diagnostic tool
├── cache/                             # Cache directory (images & geocoding)
└── README.md                          # This file
```

## 🚀 Quick Start

### 1. Set Mapbox Token

Edit `config.php` and paste your Mapbox token:

```php
define('MAPBOX_TOKEN', 'pk.eyJ1.....YOUR_TOKEN_HERE.....');
```

**Get your token**: https://account.mapbox.com/access-tokens/ (Free tier: 50,000 map loads/month)

### 2. Run Diagnostics

Use the built-in requirements checker to verify your hosting environment:

```
https://yourdomain.com/gmap/tools/requirements-check.php
```

Enter your Mapbox token to test:
- ✅ Token format validation
- ✅ API connectivity test
- ✅ All required PHP extensions
- ✅ File permissions
- ✅ Network connectivity

This ensures everything works before you start testing on your phone.

### 3. Upload Files

Upload all files to your PHP-enabled web hosting in a directory like `/public_html/gmap/`

### 4. Set Permissions

Ensure the `cache/` directory is writable (755 permissions):

```bash
chmod 755 cache/
chmod 755 cache/geocode/
```

### 5. Test in Browser

Open: `https://yourdomain.com/gmap/`

You should see a map of Berlin with controls and search box.

## 📱 Testing on Nokia 225

1. Enable mobile data/WiFi
2. Open browser
3. Navigate to: `https://yourdomain.com/gmap/`
4. Test: Type address, click Search, use arrow buttons, try map style links

## ✨ What's New

### Requirements Checker
New diagnostic tool that tests your token and hosting before deployment:

- Enter token and verify it works
- Test Mapbox Static Images API
- Test Mapbox Geocoding API
- Check all PHP requirements
- Verify file permissions
- Visual pass/fail indicators

**Access it via:** `tools/requirements-check.php`

### Dynamic Titles with Reverse Geocoding
Page titles now show human-readable location names instead of just "Map":

**Before:** `<title>Map</title>`

**After:** `<title>Bodestraße 4, 10178 Berlin, Germany - Lat: 52.52, Lon: 13.40 (Streets)</title>`

Automatically converts coordinates to location names using Mapbox Geocoding API with 1-hour caching.

### Clickable Map Style Links
Changed from radio buttons + SET button to simple text links. The currently active style appears in a different color and is not clickable.

**Before:** Radio buttons + SET button

**After:** `Streets (active) | Outdoors (link) | Satellite (link)`

## 🔧 Configuration

### Map Styles Available

- **Streets-v12**: Standard street map (default)
- **Outdoors-v12**: Terrain & trails for outdoor activities
- **Satellite-v9**: Aerial imagery

### Customizing Behavior

Edit these values in `class/MapSession.php`:

```php
const DEFAULT_LAT = 52.52;   // Default: Berlin
const DEFAULT_LON = 13.40;
const DEFAULT_ZOOM = 14;
```

## 📱 Screen Layout

**320x240 QVGA Optimized:**

```
┌─────────────────────────┐
│ Map (310×250px)         │
├─────────────────────────┤
│ [←] [→] [↑] [↓] [+][-]  │
├─────────────────────────┤
│ Street, City [SEARCH]   │
├─────────────────────────┤
│ Streets | Out | Sat     │
├─────────────────────────┤
│ Lat: 52.52 Lon: 13.40   │
└─────────────────────────┘
```

## ✅ Expected Behavior

✓ Map loads (Berlin center by default)
✓ Search "Berlin Alexanderplatz" → centers map
✓ Arrow buttons pan the map
✓ +/- buttons change zoom level
✓ Click "Satellite" link → satellite view
✓ Page title shows location name + coordinates + style
✓ All changes persist during session

## 🛠️ Troubleshooting

| Problem | Solution |
|---------|----------|
| Blank map | Check Mapbox token in `config.php` |
| Search fails | Verify `allow_url_fopen` is enabled in PHP |
| Cache errors | Create `cache/` directory with 755 permissions |
| Geocoding fails | Check Mapbox token has Geocoding API permissions |
| Out of memory | Increase `memory_limit` in PHP to 32M+ |
| Styles not changing | Verify `controller/mapstyle.php` accessible and writable |
| No map image | Use `tools/requirements-check.php` to diagnose |

## 📊 System Requirements

✅ **PHP**: 7.4 or higher
✅ **Extensions**: JSON, Session, cURL
✅ **Memory**: 32MB minimum (64M+ recommended)
✅ **Network**: Outbound HTTPS to api.mapbox.com
✅ **Tokens**: Mapbox token with `styles:tiles` and `geocoding:read` permissions

## 🎯 Current Status

**PRODUCTION READY** ✅

Upload files and test on your Nokia 225 phone! Map style links work well on feature phone browsers.

## 🔍 Testing Checklist

- [ ] Run `tools/requirements-check.php` first
- [ ] Map displays on Nokia 225 browser
- [ ] Search works and centers map correctly
- [ ] All arrow buttons pan the map
- [ ] Zoom +/- buttons work
- [ ] Map style links change the view
- [ ] Page titles show location names
- [ ] Coordinates update correctly
- [ ] Cache files created in `cache/` directory

## 📝 Support

If issues:

1. **Run the requirements checker**: `tools/requirements-check.php`
2. Verify Mapbox token has correct permissions
3. Check `cache/` directory exists and is writable
4. Review PHP error logs
5. Check hosting meets all requirements in table above

## 📄 License

Open source - free to use and modify

---

🎉 **Happy mapping on your Nokia 225!**
