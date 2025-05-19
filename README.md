# WooCommerce Manual Invoices Pro

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-8.0%2B-purple.svg)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

A comprehensive WordPress plugin that empowers WooCommerce store administrators to create manual invoices, generate secure "Pay Now" links, and send professional invoice emails to customers with ease.

---

## 🚀 Features

### ✅ **Core Functionality**

- **Manual Invoice Creation** - Create invoices directly from the admin panel
- **Customer Management** - Select existing customers or create new ones on-the-fly
- **Product Integration** - Add WooCommerce products or custom line items
- **Flexible Pricing** - Support for custom pricing, fees, shipping, and taxes
- **Secure Pay Links** - Generate WooCommerce checkout URLs for easy payment
- **Professional Emails** - Send branded invoice emails with payment buttons
- **PDF Generation** - Create downloadable PDF invoices with company branding
- **Invoice Dashboard** - Comprehensive management interface with filtering and actions

### 📊 **Dashboard Features**

- Real-time invoice statistics
- Status-based filtering (Pending, Paid, Overdue)
- Bulk actions for efficient management
- Search and pagination
- Quick actions (Send Email, Generate PDF, Clone, Delete)

### 🎨 **Customization Options**

- Company branding (logo, colors, information)
- Custom email templates
- Invoice number prefixes
- Payment terms and conditions
- Automated reminders (Premium feature)
- Late fee calculations (Premium feature)

### 🔒 **Security & Integration**

- Secure order keys for payment links
- WooCommerce native order system integration
- All payment gateways supported
- User permission controls
- AJAX-powered interface with nonce verification

---

## 📋 System Requirements

- **WordPress:** 6.0 or higher
- **WooCommerce:** 8.0 or higher
- **PHP:** 8.0 or higher
- **MySQL:** 5.6 or higher
- **Server Memory:** 128MB minimum (256MB recommended)

### Optional PDF Requirements

- **DomPDF:** For advanced PDF generation (recommended)
- **TCPDF:** Alternative PDF library
- **GD/ImageMagick:** For image processing

---

## 🛠️ Installation

### Method 1: WordPress Admin (Recommended)

1. Download the plugin zip file
2. Go to **WordPress Admin → Plugins → Add New**
3. Click **Upload Plugin** and select the zip file
4. Click **Install Now** and then **Activate**

### Method 2: FTP Upload

1. Extract the plugin files
2. Upload the `woocommerce-manual-invoices` folder to `/wp-content/plugins/`
3. Activate the plugin in **WordPress Admin → Plugins**

### Method 3: WP-CLI

```bash
wp plugin install woocommerce-manual-invoices.zip --activate
```

### Post-Installation Setup

1. Go to **WooCommerce → Invoice Settings**
2. Configure your company information
3. Upload your company logo
4. Set default invoice terms
5. Test by creating your first invoice

---

## 📖 Getting Started

### Creating Your First Invoice

1. **Navigate to Invoices**

   - Go to **WooCommerce → Invoices**
   - Click the **Create Invoice** tab

2. **Select Customer**

   - Search for existing customers by typing in the search box
   - Or create a new customer by filling in the contact details

3. **Add Invoice Items**

   - **Products:** Search and select from your WooCommerce catalog
   - **Custom Items:** Add services or non-catalog items with descriptions
   - Set quantities and adjust pricing as needed

4. **Additional Charges**

   - Add fees (handling, setup, etc.)
   - Include shipping costs
   - Apply taxes

5. **Invoice Details**

   - Set due date (defaults to 30 days)
   - Add custom notes
   - Include terms and conditions
   - Choose to send email immediately

6. **Create & Send**
   - Click **Create Invoice**
   - Invoice is automatically saved as a pending WooCommerce order
   - Customer receives email with pay now link

### Managing Invoices

#### Dashboard Overview

- View all invoices with status indicators
- Filter by status (All, Pending, Paid, Overdue)
- See total invoice value and outstanding amounts

#### Available Actions

- **👁️ View** - Open in WooCommerce order details
- **📧 Send Email** - Resend invoice email to customer
- **📄 Generate PDF** - Create/download PDF invoice
- **📋 Clone** - Duplicate invoice for similar transactions
- **🗑️ Delete** - Remove pending invoices (paid invoices protected)

#### Status Management

- **Pending** - Awaiting payment
- **Processing** - Payment received, being processed
- **Completed** - Fully paid and fulfilled
- **Manual Invoice** - Custom status for manual invoices

---

## ⚙️ Configuration

### General Settings

Navigate to **WooCommerce → Invoice Settings** to configure:

#### **Basic Options**

- **Default Due Days** - How many days until invoice is due (default: 30)
- **Auto-send Email** - Automatically email invoices when created
- **Auto-generate PDF** - Create PDF attachments automatically
- **Invoice Prefix** - Customize invoice numbering (e.g., "INV-")

#### **Company Information**

```
Company Name: [Your Business Name]
Address: [Full business address]
Phone: [Contact number]
Email: [Business email]
Logo: [Upload company logo]
Footer Text: [Custom footer message]
```

#### **Advanced Features**

- **Payment Reminders** - Automated reminder emails
- **Late Fees** - Automatic penalty calculations
- **Email Templates** - Customize email appearance

### Email Template Customization

Override email templates by copying to your theme:

```
yourtheme/
└── woocommerce/
    └── emails/
        ├── manual-invoice.php (HTML)
        └── plain/
            └── manual-invoice.php (Plain text)
```

### PDF Template Customization

Customize PDF appearance by modifying:

```
templates/pdf-invoice.php
```

---

## 🔧 Developer Documentation

### Hooks & Filters

#### Actions

```php
// Triggered when invoice is created
do_action('wc_manual_invoice_created', $order_id);

// Triggered when invoice email is sent
do_action('wc_manual_invoice_email_sent', $order_id);

// Triggered when PDF is generated
do_action('wc_manual_invoice_pdf_generated', $order_id, $pdf_path);

// Triggered before invoice is deleted
do_action('wc_manual_invoice_before_delete', $order_id);
```

#### Filters

```php
// Modify invoice email attachments
apply_filters('wc_manual_invoice_email_attachments', $attachments, $order, $email);

// Customize company information
apply_filters('wc_manual_invoice_company_info', $company_info);

// Modify PDF template data
apply_filters('wc_manual_invoice_pdf_data', $data, $order);

// Customize invoice number format
apply_filters('wc_manual_invoice_number_format', $number, $order_id);

// Add social media links to emails
apply_filters('wc_manual_invoice_social_links', $social_links);
```

### API Functions

#### Create Invoice Programmatically

```php
$invoice_data = array(
    'customer_email' => 'customer@example.com',
    'billing_first_name' => 'John',
    'billing_last_name' => 'Doe',
    'custom_items' => array(
        array(
            'name' => 'Consulting Service',
            'description' => 'Website consultation',
            'quantity' => 1,
            'total' => 150.00
        )
    ),
    'notes' => 'Thank you for your business!',
    'due_date' => date('Y-m-d', strtotime('+30 days'))
);

$order_id = WC_Manual_Invoice_Generator::create_invoice($invoice_data);
```

#### Generate PDF

```php
$pdf_path = WC_Manual_Invoice_PDF::generate_pdf($order_id);
$download_url = WC_Manual_Invoice_PDF::get_pdf_download_url($order_id);
```

#### Send Email

```php
WC()->mailer()->emails['WC_Manual_Invoice_Email']->trigger($order_id);
```

### Database Schema

#### Invoice Meta Fields

```php
_is_manual_invoice          // Boolean flag
_manual_invoice_notes       // Custom notes
_manual_invoice_terms       // Terms & conditions
_manual_invoice_due_date    // Due date
_invoice_pdf_generated      // PDF generation timestamp
_invoice_pdf_path           // PDF file path
_invoice_last_sent          // Last email sent timestamp
```

#### Reminder Tracking Table

```sql
wp_wc_manual_invoice_reminders
├── id (int)
├── order_id (int)
├── reminder_date (datetime)
├── reminder_type (varchar)
├── sent (tinyint)
└── created_at (datetime)
```

---

## 📱 User Interface

### Admin Dashboard

- **Clean, Modern Design** - Intuitive interface matching WooCommerce style
- **Responsive Layout** - Works on desktop, tablet, and mobile
- **Real-time Updates** - AJAX-powered for smooth user experience
- **Bulk Actions** - Efficient management of multiple invoices

### Email Templates

- **Professional Appearance** - Branded, responsive email design
- **Mobile-Optimized** - Renders perfectly on all devices
- **Call-to-Action Buttons** - Prominent pay now buttons
- **Status Indicators** - Visual payment status badges

### PDF Invoices

- **Professional Layout** - Clean, business-ready format
- **Company Branding** - Logo and company information
- **Detailed Breakdowns** - Itemized costs and totals
- **Payment Instructions** - Clear payment directions

---

## 🔍 Troubleshooting

### Common Issues

#### **Emails Not Sending**

```
✓ Check WooCommerce email settings
✓ Verify SMTP configuration
✓ Test with WooCommerce test emails
✓ Check email logs in WooCommerce
```

#### **PDF Generation Failing**

```
✓ Ensure adequate server memory (256MB+)
✓ Check file permissions on uploads directory
✓ Install DomPDF or TCPDF library
✓ Review error logs for specific issues
```

#### **Payment Links Not Working**

```
✓ Verify WooCommerce permalink settings
✓ Ensure payment gateways are active
✓ Check SSL certificate validity
✓ Test with different payment methods
```

#### **Customer Search Not Working**

```
✓ Clear browser cache and cookies
✓ Check for JavaScript errors in console
✓ Verify AJAX endpoints are accessible
✓ Confirm user has proper permissions
```

### Debug Mode

Enable WordPress debug logging:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check logs at: `/wp-content/debug.log`

### Performance Optimization

#### Server Requirements

- **PHP Memory:** 256MB minimum
- **Max Execution Time:** 120 seconds for PDF generation
- **Upload Max Size:** 10MB for company logos

#### Recommended Settings

```php
// In wp-config.php or .htaccess
ini_set('memory_limit', '256M');
ini_set('max_execution_time', 120);
ini_set('upload_max_filesize', '10M');
```

---

## 🔄 Update & Maintenance

### Version Updates

1. **Always backup** your site before updating
2. **Test in staging** environment first
3. **Check compatibility** with current WooCommerce version
4. **Review changelog** for breaking changes

### Database Maintenance

- **Archive old invoices** periodically to maintain performance
- **Clean up PDF files** older than 1 year (configurable)
- **Optimize database tables** regularly

### Backup Considerations

- **Invoice attachments** in `/wp-content/uploads/wc-manual-invoices/`
- **Plugin settings** in WordPress options table
- **Order meta data** containing invoice information

---

## 🤝 Support & Community

### Getting Help

1. **Documentation** - Check this README and inline help
2. **WooCommerce Forums** - Community support
3. **Plugin Support** - Email support for registered users
4. **GitHub Issues** - Bug reports and feature requests

### Reporting Bugs

When reporting issues, please include:

- WordPress version
- WooCommerce version
- Plugin version
- PHP version
- Error messages/logs
- Steps to reproduce

### Feature Requests

We welcome suggestions for new features! Please submit requests through:

- GitHub Issues
- Support email
- WooCommerce community forums

---

## 📊 Performance & Scalability

### Benchmarks

- **Invoice Creation:** ~2-3 seconds
- **PDF Generation:** ~5-10 seconds (depending on complexity)
- **Email Sending:** ~1-2 seconds per email
- **Dashboard Loading:** ~1-2 seconds (up to 1000 invoices)

### Optimization Tips

1. **Use caching** plugins for better performance
2. **Optimize images** in email templates
3. **Limit invoice history** displayed in dashboard
4. **Use CDN** for static assets

### Scaling Considerations

- **Large volume:** Consider dedicated email service (SMTP)
- **High traffic:** Implement caching strategies
- **Multiple stores:** Use multisite-compatible setup

---

## 🔐 Security Considerations

### Data Protection

- All payment links use **secure order keys**
- Customer data is **encrypted** in transit
- **Nonce verification** on all admin actions
- **Permission checks** for all functionality

### Best Practices

1. **Regular updates** - Keep plugin and dependencies current
2. **Strong passwords** - Use complex admin passwords
3. **SSL certificates** - Ensure HTTPS for all transactions
4. **Access control** - Limit admin access to necessary users
5. **Backup strategy** - Regular automated backups

### Compliance

- **GDPR ready** - Customer data handling compliant
- **PCI considerations** - No sensitive payment data stored
- **Privacy policies** - Update to reflect invoice data collection

---

## 📈 Analytics & Reporting

### Built-in Metrics

- Total invoices created
- Pending vs. paid amounts
- Payment conversion rates
- Average payment time

### Integration Options

- **Google Analytics** - Track payment completions
- **Custom reporting** - Export invoice data
- **WooCommerce Analytics** - Native integration

---

## 🌐 Internationalization

### Language Support

- **Translation ready** - All strings are translatable
- **RTL support** - Right-to-left language compatible
- **Currency formatting** - Respects WooCommerce settings
- **Date formatting** - Localized date displays

### Available Translations

- English (default)
- Spanish
- French
- German
- More languages available through community contributions

---

## 📄 License & Legal

### License Information

This plugin is licensed under the **GPL v2 or later**.

```
WooCommerce Manual Invoices Pro
Copyright (C) 2024

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
```

### Third-Party Libraries

- **DomPDF** - PDF generation (LGPL)
- **TCPDF** - Alternative PDF library (LGPL)
- **Select2** - Enhanced select boxes (MIT)

### Attribution

This plugin includes icons from:

- **Dashicons** - WordPress icon set
- **Feather Icons** - MIT licensed

---

## 🚧 Roadmap

### Upcoming Features

- [ ] **Recurring Invoices** - Automated invoice generation
- [ ] **Multi-currency Support** - International transactions
- [ ] **Advanced Templates** - More design options
- [ ] **API Endpoints** - REST API for external integrations
- [ ] **Webhooks** - Real-time notifications
- [ ] **Mobile App** - Native iOS/Android app

### Long-term Vision

- Integration with popular accounting software
- Advanced analytics and reporting
- Multi-vendor marketplace support
- Enterprise-grade features

---

## 👨‍💻 Contributing

We welcome contributions from the community!

### Getting Started

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

### Coding Standards

- Follow **WordPress Coding Standards**
- Use **PHPDoc** for documentation
- Write **meaningful commit messages**
- Include **unit tests** for new features

### Development Setup

```bash
# Clone the repository
git clone https://github.com/your-repo/woocommerce-manual-invoices.git

# Install dependencies
composer install
npm install

# Run tests
phpunit
npm test
```

---

## 📞 Contact & Support

### Support Channels

- **Email:** support@yourplugin.com
- **Forums:** WooCommerce Community
- **Documentation:** https://docs.yourplugin.com
- **GitHub:** https://github.com/your-repo/woocommerce-manual-invoices

### Business Inquiries

- **Custom Development:** custom@yourplugin.com
- **Enterprise Licensing:** enterprise@yourplugin.com
- **Partnerships:** partners@yourplugin.com

---

## 🙏 Acknowledgments

### Credits

- **Wbcom Designs** - Plugin development and maintenance
- **WooCommerce Team** - For the excellent e-commerce platform
- **WordPress Community** - For the robust CMS foundation
- **Contributors** - All developers who have contributed to this project
- **Beta Testers** - Users who helped test and improve the plugin

### Special Thanks

- The WooCommerce community for feedback and feature requests
- Translation contributors for making the plugin accessible worldwide
- Open source library maintainers for their excellent work

---

**Made with ❤️ by Wbcom Designs for the WooCommerce community**

---

_Last updated: November 2024 | Version 1.0.0_
