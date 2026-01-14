<?php
    defined( 'ABSPATH' ) || exit;

    // Get current user data
    $user_id = $current_user->ID;

    if ( ! isset( $user_id ) || empty( $user_id ) || ! isset( $_GET['data-security'] ) || ! wp_verify_nonce( $_GET['data-security'], 'wcrb_nonce_printscreen' ) ) {
        echo esc_html__( "Something is wrong with your submission!", "computer-repair-shop" );
        exit();
    }
    //$dasboard     = WCRB_MYACCOUNT_DASHBOARD::getInstance();
    if ( ! isset( $_GET['job_id'] ) || ! $dasboard->have_job_access( sanitize_text_field( $_GET['job_id'] ) ) ) {
        echo esc_html__( "Something is wrong with your submission!", "computer-repair-shop" );
        exit();
    }
?>
<style>
        .content {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .form-section {
            flex: 1;
            min-width: 350px;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .preview-section {
            flex: 1;
            min-width: 350px;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
        }
        
        input[type="text"], select, textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border 0.3s;
        }
        
        input[type="text"]:focus, select:focus, textarea:focus {
            border-color: #2575fc;
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 117, 252, 0.1);
        }
        
        .label-preview {
            border: 2px dashed #ccc;
            min-height: 350px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 25px;
            background-color: #f9f9f9;
            position: relative;
        }
        
        .qrcode-container {
            margin-bottom: 25px;
        }
        
        .label-data {
            text-align: center;
            font-family: 'Courier New', monospace;
        }
        
        .case-number {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #2575fc;
        }
        
        .job-number {
            font-size: 20px;
            color: #333;
        }
        
        .print-options {
            margin-top: 30px;
        }
        
        .print-options h3 {
            margin-bottom: 15px;
            color: #444;
        }
        
        .option-group {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .option-item {
            flex: 1;
            min-width: 140px;
        }
        
       
        footer {
            text-align: center;
            margin-top: 40px;
            color: #666;
            font-size: 14px;
            padding-top: 20px;
            border-top: 1px solid #eaeaea;
        }
        
        .printer-settings {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .printer-settings h4 {
            margin-bottom: 15px;
            color: #495057;
        }
        
        .setting-item {
            margin-bottom: 12px;
            font-size: 14px;
        }
        
        .setting-label {
            font-weight: 600;
            color: #495057;
            display: inline-block;
            width: 120px;
        }
        
        .setting-value {
            color: #6c757d;
        }
        
        .compatibility-note {
            background-color: #e7f3ff;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 14px;
            color: #0c5460;
            border-left: 4px solid #2575fc;
        }
        
        .print-section {
            display: none;
        }
        
        @media print {
            body * {
                visibility: hidden;
            }
            
            .print-section, .print-section * {
                visibility: visible;
            }
            
            .print-section {
                position: fixed;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                display: block;
                background: white;
                z-index: 9999;
                padding: 0;
                margin: 0;
            }
            
            .print-label {
                box-sizing: border-box;
                page-break-inside: avoid;
            }
            
            .no-print {
                display: none !important;
            }
        }
        
        @media (max-width: 768px) {
            .content {
                flex-direction: column;
            }
            .form-section, .preview-section {
                width: 100%;
                min-width: 100%;
            }
        }
        
        .loading {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        
        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
<main class="dashboard-content container-fluid py-4">
        
        <div class="content">
            <div class="form-section">
                <h2>Label Information</h2>
                
                <div class="form-group">
                    <label for="printerType">Selected Printer</label>
                    <select id="printerType">
                        <option value="argox">Argox Printer</option>
                        <option value="zebra">Zebra Printer</option>
                        <option value="brother">Brother Printer</option>
                        <option value="dymo">DYMO Printer</option>
                        <option value="generic">Generic/Other Printer</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="caseNumber">Case Number</label>
                    <input type="text" id="caseNumber" placeholder="Enter case number (e.g., CASE-2024-001)">
                </div>
                
                <div class="form-group">
                    <label for="jobNumber">Job Number</label>
                    <input type="text" id="jobNumber" placeholder="Enter job number (e.g., JOB-7890)">
                </div>
                
                <div class="form-group">
                    <label for="qrContent">QR Code Content (URL or Text)</label>
                    <input type="text" id="qrContent" placeholder="Enter URL or text for QR code">
                </div>
                
                <div class="form-group">
                    <label for="labelWidth">Label Width (inches)</label>
                    <select id="labelWidth">
                        <option value="1">1 inch (25mm)</option>
                        <option value="2">2 inches (50mm)</option>
                        <option value="3" selected>3 inches (76mm)</option>
                        <option value="4">4 inches (102mm)</option>
                        <option value="4.645">4.645 inches (118mm - Argox Max)</option>
                        <option value="custom">Custom Width</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="labelHeight">Label Height (inches)</label>
                    <select id="labelHeight">
                        <option value="1">1 inch (25mm)</option>
                        <option value="2" selected>2 inches (50mm)</option>
                        <option value="3">3 inches (76mm)</option>
                        <option value="4">4 inches (102mm)</option>
                        <option value="6">6 inches (152mm)</option>
                        <option value="custom">Custom Height</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="printTechnology">Print Technology</label>
                    <select id="printTechnology">
                        <option value="thermal_transfer">Thermal Transfer (with ribbon)</option>
                        <option value="direct_thermal">Direct Thermal (no ribbon)</option>
                        <option value="laser">Laser/Inkjet (standard paper)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="additionalData">Additional Data (optional)</label>
                    <textarea id="additionalData" rows="3" placeholder="Add any additional text to appear on label"></textarea>
                </div>
                
                <button class="btn btn-generate" id="generateBtn">Generate Label</button>
            </div>
            
            <div class="preview-section">
                <h2>Label Preview</h2>
                
                <div class="printer-settings">
                    <h4>Current Printer Settings</h4>
                    <div class="setting-item">
                        <span class="setting-label">Printer:</span>
                        <span class="setting-value" id="currentPrinter">Argox Printer</span>
                    </div>
                    <div class="setting-item">
                        <span class="setting-label">Label Size:</span>
                        <span class="setting-value" id="currentSize">3" x 2"</span>
                    </div>
                    <div class="setting-item">
                        <span class="setting-label">Technology:</span>
                        <span class="setting-value" id="currentTech">Thermal Transfer</span>
                    </div>
                </div>
                
                <div class="label-preview" id="labelPreview">
                    <div class="loading" id="loading">
                        <div class="loading-spinner"></div>
                        <div>Generating QR Code...</div>
                    </div>
                    <div class="qrcode-container" id="qrcodeContainer">
                        <!-- QR code will be inserted here -->
                    </div>
                    <div class="label-data">
                        <div class="case-number" id="previewCaseNumber">CASE-2024-001</div>
                        <div class="job-number" id="previewJobNumber">JOB-7890</div>
                        <div id="previewAdditional" style="margin-top: 10px; font-size: 14px; color: #666;"></div>
                    </div>
                </div>
                
                <div class="compatibility-note">
                    <strong>Note:</strong> This label is optimized for your selected printer type. Ensure your printer is properly configured for the selected label size.
                </div>
                
                <div class="print-options">
                    <h3>Print Settings</h3>
                    <div class="option-group">
                        <div class="option-item">
                            <label for="copies">Copies</label>
                            <select id="copies">
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="5">5</option>
                                <option value="10">10</option>
                            </select>
                        </div>
                        
                        <div class="option-item">
                            <label for="orientation">Orientation</label>
                            <select id="orientation">
                                <option value="portrait">Portrait</option>
                                <option value="landscape">Landscape</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="option-group">
                        <div class="option-item">
                            <label for="printQuality">Print Quality</label>
                            <select id="printQuality">
                                <option value="normal">Normal</option>
                                <option value="high" selected>High</option>
                                <option value="draft">Draft</option>
                            </select>
                        </div>
                        
                        <div class="option-item">
                            <label for="margin">Margin (inches)</label>
                            <select id="margin">
                                <option value="0.1">0.1" (Small)</option>
                                <option value="0.25" selected>0.25" (Medium)</option>
                                <option value="0.5">0.5" (Large)</option>
                                <option value="0">0" (No Margin)</option>
                            </select>
                        </div>
                    </div>
                    
                    <button class="btn btn-print" id="printBtn">Print Label</button>
                </div>
            </div>
        </div>
        
        
        <footer>
            <p>Universal Barcode Label Generator | Compatible with most label printers via standard browser printing</p>
            <p>For industrial printers, check if your printer supports ZPL, EPL, or CPCL for direct printing commands</p>
        </footer>
</main>
    
<!-- Print Section (hidden until printing) - FIXED -->
<div id="printSection" class="print-section"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // DOM Elements
            const printerTypeSelect = document.getElementById('printerType');
            const caseNumberInput = document.getElementById('caseNumber');
            const jobNumberInput = document.getElementById('jobNumber');
            const qrContentInput = document.getElementById('qrContent');
            const labelWidthSelect = document.getElementById('labelWidth');
            const labelHeightSelect = document.getElementById('labelHeight');
            const printTechnologySelect = document.getElementById('printTechnology');
            const additionalDataTextarea = document.getElementById('additionalData');
            const generateBtn = document.getElementById('generateBtn');
            const printBtn = document.getElementById('printBtn');
            
            const previewCaseNumber = document.getElementById('previewCaseNumber');
            const previewJobNumber = document.getElementById('previewJobNumber');
            const previewAdditional = document.getElementById('previewAdditional');
            const qrcodeContainer = document.getElementById('qrcodeContainer');
            const labelPreview = document.getElementById('labelPreview');
            const printSection = document.getElementById('printSection');
            const loadingElement = document.getElementById('loading');
            
            const currentPrinterSpan = document.getElementById('currentPrinter');
            const currentSizeSpan = document.getElementById('currentSize');
            const currentTechSpan = document.getElementById('currentTech');
            
            const copiesSelect = document.getElementById('copies');
            const orientationSelect = document.getElementById('orientation');
            const printQualitySelect = document.getElementById('printQuality');
            const marginSelect = document.getElementById('margin');
            
            let currentPrinter = 'argox';
            
            // Printer configurations
            const printerConfigs = {
                argox: {
                    name: 'Argox Printer',
                    maxWidth: 4.645,
                    minWidth: 0.787,
                    defaultWidth: 3,
                    defaultHeight: 2,
                    tech: 'thermal_transfer',
                    instructions: 'Supports both thermal transfer and direct thermal media.'
                },
                zebra: {
                    name: 'Zebra Printer',
                    maxWidth: 4.1,
                    minWidth: 0.5,
                    defaultWidth: 3,
                    defaultHeight: 2,
                    tech: 'thermal_transfer',
                    instructions: 'Industry standard. Use ZPL for production environments.'
                },
                brother: {
                    name: 'Brother Printer',
                    maxWidth: 4.1,
                    minWidth: 0.5,
                    defaultWidth: 2.3,
                    defaultHeight: 1.5,
                    tech: 'direct_thermal',
                    instructions: 'Common for office and light industrial use.'
                },
                dymo: {
                    name: 'DYMO Printer',
                    maxWidth: 4.25,
                    minWidth: 0.75,
                    defaultWidth: 2.1,
                    defaultHeight: 1.3,
                    tech: 'direct_thermal',
                    instructions: 'Popular for desktop labeling and shipping labels.'
                },
                generic: {
                    name: 'Generic/Other Printer',
                    maxWidth: 8.5,
                    minWidth: 0.5,
                    defaultWidth: 3,
                    defaultHeight: 2,
                    tech: 'laser',
                    instructions: 'Adjust settings to match your printer capabilities.'
                }
            };
            
            // Set default values
            caseNumberInput.value = "CASE-2024-001";
            jobNumberInput.value = "JOB-7890";
            qrContentInput.value = "http://127.0.0.1/wordpress/s/status_B_G1765597406";
            
            // Initialize
            initializePrinter('argox');
            generateLabel();
            
            // Printer type select change
            printerTypeSelect.addEventListener('change', function() {
                const printerType = this.value;
                currentPrinter = printerType;
               
                // Initialize with selected printer
                initializePrinter(printerType);
            });
            
            // Initialize printer settings
            function initializePrinter(printerType) {
                const config = printerConfigs[printerType];
                
                // Update display
                currentPrinterSpan.textContent = config.name;
                currentTechSpan.textContent = printTechnologySelect.options[printTechnologySelect.selectedIndex].text;
                
                // Set default dimensions
                labelWidthSelect.value = config.defaultWidth;
                labelHeightSelect.value = config.defaultHeight;
                
                // Set default technology
                printTechnologySelect.value = config.tech;
                
                // Update size display
                updateSizeDisplay();
                
                // Update label preview size
                updateLabelSize();
            }
            
            // Update size display
            function updateSizeDisplay() {
                const width = labelWidthSelect.value;
                const height = labelHeightSelect.value;
                currentSizeSpan.textContent = `${width}" x ${height}"`;
            }
            
            // Update label size in preview
            function updateLabelSize() {
                const widthInches = parseFloat(labelWidthSelect.value);
                const heightInches = parseFloat(labelHeightSelect.value);
                
                // Convert inches to pixels for display (assuming 96 DPI for screens)
                const widthPx = widthInches * 96;
                const heightPx = heightInches * 96;
                
                // Limit maximum display size
                const maxDisplayWidth = 500;
                const maxDisplayHeight = 400;
                
                let displayWidth = widthPx;
                let displayHeight = heightPx;
                
                // Scale down if too large
                if (displayWidth > maxDisplayWidth || displayHeight > maxDisplayHeight) {
                    const scale = Math.min(maxDisplayWidth / displayWidth, maxDisplayHeight / displayHeight);
                    displayWidth *= scale;
                    displayHeight *= scale;
                }
                
                // Apply to preview
                labelPreview.style.width = `${displayWidth}px`;
                labelPreview.style.height = `${displayHeight}px`;
                
                // Update size display
                updateSizeDisplay();
            }
            
            // Generate QR code using QuickChart.io
            async function generateQRCode(text) {
                if (!text) {
                    text = "http://127.0.0.1/wordpress/s/status_B_G1765597406";
                }
                
                // URL encode the text
                const encodedText = encodeURIComponent(text);
                
                // QuickChart.io QR code URL
                const qrCodeUrl = `https://quickchart.io/qr?text=${encodedText}&size=150`;
                
                // Return the image URL
                return qrCodeUrl;
            }
            
            // Generate label function
            async function generateLabel() {
                // Show loading
                loadingElement.style.display = 'block';
                qrcodeContainer.innerHTML = '';
                
                const caseNumber = caseNumberInput.value || "CASE-2024-001";
                const jobNumber = jobNumberInput.value || "JOB-7890";
                const qrContent = qrContentInput.value || "http://127.0.0.1/wordpress/s/status_B_G1765597406";
                const additionalData = additionalDataTextarea.value;
                
                // Update preview text
                previewCaseNumber.textContent = caseNumber;
                previewJobNumber.textContent = jobNumber;
                
                if (additionalData) {
                    previewAdditional.textContent = additionalData;
                    previewAdditional.style.display = 'block';
                } else {
                    previewAdditional.style.display = 'none';
                }
                
                // Generate QR code
                try {
                    const qrCodeUrl = await generateQRCode(qrContent);
                    
                    // Create QR code image
                    const qrImage = document.createElement('img');
                    qrImage.src = qrCodeUrl;
                    qrImage.alt = "QR Code";
                    qrImage.style.maxWidth = "100%";
                    qrImage.style.height = "auto";
                    
                    // Add to container
                    qrcodeContainer.appendChild(qrImage);
                    
                    // Hide loading
                    loadingElement.style.display = 'none';
                    
                } catch (error) {
                    console.error("Error generating QR code:", error);
                    qrcodeContainer.innerHTML = '<div style="color: #dc3545; text-align: center;">Failed to generate QR code. Please check your URL.</div>';
                    loadingElement.style.display = 'none';
                }
                
                // Update label size
                updateLabelSize();
                
                // Update technology display
                currentTechSpan.textContent = printTechnologySelect.options[printTechnologySelect.selectedIndex].text;
            }
            
            // Print function - FIXED
            async function printLabels() {
                const caseNumber = caseNumberInput.value || "CASE-2024-001";
                const jobNumber = jobNumberInput.value || "JOB-7890";
                const qrContent = qrContentInput.value || "http://127.0.0.1/wordpress/s/status_B_G1765597406";
                const additionalData = additionalDataTextarea.value;
                const copies = parseInt(copiesSelect.value);
                const orientation = orientationSelect.value;
                const margin = marginSelect.value;
                const printerType = currentPrinter;
                
                // Clear print section
                printSection.innerHTML = '';
                
                // Set print section dimensions
                const widthInches = parseFloat(labelWidthSelect.value);
                const heightInches = parseFloat(labelHeightSelect.value);
                
                // Generate QR code URL for printing
                const encodedText = encodeURIComponent(qrContent);
                const qrCodeUrl = `https://quickchart.io/qr?text=${encodedText}&size=200`;
                
                // Create label copies for printing
                for (let i = 0; i < copies; i++) {
                    const printLabel = document.createElement('div');
                    printLabel.className = 'print-label';
                    printLabel.style.cssText = `
                        width: ${widthInches}in;
                        height: ${heightInches}in;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        justify-content: center;
                        padding: ${margin}in;
                        margin: 0;
                        font-family: 'Courier New', monospace;
                        border: 1px solid #000;
                        box-sizing: border-box;
                    `;
                    
                    // Create QR code for print
                    const printQrImg = document.createElement('img');
                    printQrImg.src = qrCodeUrl;
                    printQrImg.alt = "QR Code";
                    printQrImg.style.width = '1.5in';
                    printQrImg.style.height = '1.5in';
                    printQrImg.style.marginBottom = '0.1in';
                    
                    // Create text for print
                    const printText = document.createElement('div');
                    printText.style.textAlign = 'center';
                    printText.style.marginTop = '0.1in';
                    
                    const caseText = document.createElement('div');
                    caseText.textContent = caseNumber;
                    caseText.style.fontSize = '0.3in';
                    caseText.style.fontWeight = 'bold';
                    caseText.style.color = '#000000';
                    caseText.style.marginBottom = '0.05in';
                    
                    const jobText = document.createElement('div');
                    jobText.textContent = jobNumber;
                    jobText.style.fontSize = '0.25in';
                    jobText.style.color = '#000000';
                    jobText.style.marginBottom = '0.05in';
                    
                    printText.appendChild(caseText);
                    printText.appendChild(jobText);
                    
                    // Add additional data if present
                    if (additionalData) {
                        const additionalText = document.createElement('div');
                        additionalText.textContent = additionalData;
                        additionalText.style.fontSize = '0.2in';
                        additionalText.style.color = '#000000';
                        additionalText.style.marginTop = '0.05in';
                        printText.appendChild(additionalText);
                    }
                    
                    printLabel.appendChild(printQrImg);
                    printLabel.appendChild(printText);
                    printSection.appendChild(printLabel);
                    
                    // Add page break after each label
                    if (i < copies - 1) {
                        const pageBreak = document.createElement('div');
                        pageBreak.style.pageBreakAfter = 'always';
                        printSection.appendChild(pageBreak);
                    }
                }
                
                // Set print styling
                const style = document.createElement('style');
                style.innerHTML = `
                    @media print {
                        @page {
                            size: ${widthInches}in ${heightInches}in;
                            margin: 0;
                        }
                        body {
                            margin: 0;
                            padding: 0;
                        }
                        .print-section {
                            display: block !important;
                        }
                        .print-label {
                            border: none !important;
                        }
                    }
                `;
                document.head.appendChild(style);
                
                // Trigger print after a short delay to ensure content is loaded
                setTimeout(() => {
                    window.print();
                    // Remove the style after printing
                    document.head.removeChild(style);
                }, 300);
            }
            
            // Event listeners
            generateBtn.addEventListener('click', generateLabel);
            printBtn.addEventListener('click', printLabels);
            
            // Auto-update when inputs change
            caseNumberInput.addEventListener('input', generateLabel);
            jobNumberInput.addEventListener('input', generateLabel);
            qrContentInput.addEventListener('input', generateLabel);
            additionalDataTextarea.addEventListener('input', generateLabel);
            
            labelWidthSelect.addEventListener('change', function() {
                if (this.value === 'custom') {
                    const customWidth = prompt('Enter custom width in inches (e.g., 2.5):', '2.5');
                    if (customWidth && !isNaN(customWidth)) {
                        this.innerHTML = `<option value="${customWidth}">${customWidth} inches (custom)</option>` + 
                                         this.innerHTML.replace(`<option value="custom">Custom Width</option>`, '');
                        this.value = customWidth;
                    } else {
                        this.value = '3';
                    }
                }
                generateLabel();
            });
            
            labelHeightSelect.addEventListener('change', function() {
                if (this.value === 'custom') {
                    const customHeight = prompt('Enter custom height in inches (e.g., 1.75):', '1.75');
                    if (customHeight && !isNaN(customHeight)) {
                        this.innerHTML = `<option value="${customHeight}">${customHeight} inches (custom)</option>` + 
                                         this.innerHTML.replace(`<option value="custom">Custom Height</option>`, '');
                        this.value = customHeight;
                    } else {
                        this.value = '2';
                    }
                }
                generateLabel();
            });
            
            printTechnologySelect.addEventListener('change', generateLabel);
            
            // Initialize
            generateLabel();
        });
    </script>