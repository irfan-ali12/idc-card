# Zebra CC300 Printer Setup for IDC Cards - Edge-to-Edge Printing

## Critical Setup Steps for 100% Coverage (No Gaps)

This plugin has been optimized specifically for **Zebra CC300** printers with **landscape orientation** (85.6mm × 53.98mm), **300 DPI**, and **0.8mm bleed per side** for perfect edge-to-edge printing.

### 1. Force Page Size + Zero Margins + 0.8mm Bleed (DONE IN CODE)
The CSS now uses:
```css
@page { size: 85.6mm 53.98mm; margin: 0; }
```
With precise **0.8mm bleed on all sides** (1.6mm total) for guaranteed coverage.

### 2. Disable Auto Scaling in Chrome
**Use Chrome's System Dialog:**
1. Press `Ctrl+Shift+P` 
2. Type "Print using system dialog"
3. In the system dialog, configure:
   - **Scale**: 100% (NOT "Fit to Page")
   - **Margins**: None
   - **Fit to Page**: OFF
   - **Headers/Footers**: OFF

### 3. Zebra CC300 Specific Driver Settings
**In Zebra CC300 Printing Preferences:**
- **Card Type**: CR80 (54mm x 86mm)
- **Print Quality**: 300 DPI (High Quality)
- **Duplex/Double-Sided**: ENABLE (Critical for both sides)
- **Edge-to-Edge**: ON/Enabled
- **Image to Edge**: ON/Enabled  
- **X/Y Image Offset**: 0.0mm
- **Print Method**: Thermal Transfer (recommended)
- **Ribbon Type**: YMCKO (for full color duplex)
- **Card Thickness**: Standard (30 mil)
- **Print Area**: Full Card
- **Back Side Printing**: ON/Enabled

### 5. Optimized Bleed Implementation (DONE IN CODE)
The design now includes **0.8mm bleed per side** for professional printing:
- Background extends 0.8mm past the card edges on all sides
- Total background size: 87.2mm × 55.58mm (card + bleed)
- Content positioned in safe area (0.8mm from edges)
- PDF shows complete coverage with proper bleed
- Handles Zebra CC300 tolerance perfectly

### Troubleshooting

### PDF Verification Steps

#### Before Printing - Verify 100% Coverage:
1. **Save as PDF first**: Use "Print to PDF" to verify coverage
2. **Check PDF edges**: Open PDF and zoom to 200-400%
3. **Verify no white gaps**: Background should extend to all edges
4. **Content positioning**: Text/images should be properly centered in safe area

#### Expected PDF Appearance:
- **Background**: Completely fills PDF with no white borders
- **Dimensions**: 85.6mm × 53.98mm with full coverage
- **Content**: Positioned in center with 2mm safe margins
- **Quality**: Sharp, crisp at 300 DPI

### Troubleshooting

#### If PDF still shows gaps:
1. **Clear browser cache** completely and reload
2. **Check CSS loading**: Ensure all stylesheets are loaded
3. **Browser zoom**: Set to exactly 100% before printing
4. **Print dialog**: Always use system dialog, never browser print

#### If Zebra CC300 still shows gaps after PDF is perfect:
1. **CC300 Driver Updates**:
   - Download latest CC300 driver from Zebra website
   - Ensure driver version supports edge-to-edge printing

2. **CC300 Hardware Settings**:
   - Card thickness sensor: Ensure proper detection
   - Card alignment: Check mechanical guides
   - Print head pressure: May need adjustment for edge coverage

3. **CC300 Advanced Settings**:
   - Print density: Increase slightly (but not too much)
   - Print speed: Use medium speed for better edge quality
   - Ribbon tension: Ensure proper ribbon installation

#### For optimal quality:
- Use high-quality card stock (CR80 standard)
- Ensure card stock is clean and properly aligned
- Check printer head cleanliness
- Use recommended ribbon/ink cartridges

### Technical Details

The print optimization includes:
- Absolute positioning to eliminate browser margins
- Exact canvas dimensions matching CR80 standard
- Hardware acceleration for smooth rendering
- High DPI settings for crisp output
- Edge-to-edge background sizing
- Removed border radius for seamless printing

### Support

If you continue to experience printing issues:
1. Check your Zebra printer manual for CR80 card printing instructions
2. Verify your printer supports the exact dimensions
3. Test with different card stock
4. Contact your printer manufacturer for driver updates