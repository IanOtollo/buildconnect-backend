# BuildConnect PHP Backend

Clean, professional PHP backend for BuildConnect contractor marketplace.

## 🚀 Features

- ✅ User authentication (JWT tokens)
- ✅ Role-based access (Client, Contractor, Admin)
- ✅ Contractor verification workflow
- ✅ Service request management
- ✅ Document uploads
- ✅ Notifications system
- ✅ RESTful API
- ✅ MySQL database
- ✅ CORS enabled

## 📁 Project Structure

```
buildconnect-php/
├── api/
│   ├── auth/
│   │   ├── register.php      # User registration
│   │   └── login.php          # User login
│   ├── contractors/
│   │   ├── list.php           # List contractors
│   │   ├── profile.php        # Contractor profile
│   │   └── upload.php         # Upload documents
│   ├── admin/
│   │   ├── verify.php         # Verify contractors
│   │   └── dashboard.php      # Admin dashboard
│   ├── requests/
│   │   ├── create.php         # Create service request
│   │   ├── list.php           # List requests
│   │   └── respond.php        # Accept/reject requests
│   ├── notifications/
│   │   └── get.php            # Notifications
│   └── categories/
│       └── list.php           # List categories
├── config/
│   └── database.php           # DB config + helpers
├── database/
│   └── schema.sql             # Database structure
├── uploads/                   # File storage
├── .htaccess                  # URL rewriting
├── index.php                  # API entry point
└── README.md                  # This file
```

## 🗄️ Database Setup

1. Create MySQL database:
```sql
CREATE DATABASE buildconnect;
```

2. Import schema:
```bash
mysql -u root -p buildconnect < database/schema.sql
```

3. Default admin login:
- Email: `admin@buildconnect.com`
- Password: `admin123`

## 🔧 Configuration

Create `.env` or set environment variables:

```bash
DB_HOST=localhost
DB_NAME=buildconnect
DB_USER=root
DB_PASS=your_password
JWT_SECRET=your-secret-key
```

Or configure directly in `config/database.php`

## 🚀 Deployment Options

### Option 1: Railway (Recommended)

1. Push to GitHub:
```bash
git init
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/YOUR_USERNAME/buildconnect-backend.git
git push -u origin main
```

2. Go to [Railway.app](https://railway.app)
3. Click "New Project" → "Deploy from GitHub"
4. Select your repository
5. Add MySQL service (Railway will auto-configure)
6. Add environment variables:
   - `JWT_SECRET` = your-secret-key
7. Deploy!

Railway will detect PHP automatically and deploy.

### Option 2: Traditional PHP Hosting

Upload files via FTP to your hosting provider.

1. Upload all files to `public_html/` or `www/`
2. Import `database/schema.sql` via phpMyAdmin
3. Update `config/database.php` with your DB credentials
4. Set permissions:
```bash
chmod 755 -R .
chmod 777 -R uploads/
```

### Option 3: Docker (Local Development)

```bash
docker-compose up -d
```

## 📡 API Endpoints

### Authentication

**Register** (Client or Contractor)
```
POST /api/auth/register
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123",
  "full_name": "John Doe",
  "phone": "+254700000000",
  "role": "client", // or "contractor"
  
  // If contractor, include:
  "business_name": "ABC Construction",
  "category": "Plumbing",
  "location": "Nairobi",
  "years_of_experience": 5,
  "bio": "Professional plumber..."
}
```

**Login**
```
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password123"
}

Response:
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "user": {
    "id": 1,
    "email": "user@example.com",
    "role": "client"
  }
}
```

### Contractors

**List All Contractors**
```
GET /api/contractors/list?category=Plumbing&search=ABC
```

**Get Contractor Profile**
```
GET /api/contractors/profile?id=1
```

**Upload Documents** (Contractor only)
```
POST /api/contractors/upload
Authorization: Bearer {token}
Content-Type: multipart/form-data

Files:
- kra_pin (file)
- business_permit (file)
- certificate (file)
- id_copy (file)
```

### Admin

**Dashboard**
```
GET /api/admin/dashboard
Authorization: Bearer {admin_token}
```

**Verify Contractor**
```
POST /api/admin/verify
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "contractor_id": 1,
  "action": "approve", // or "reject"
  "reason": "Optional rejection reason"
}
```

### Service Requests

**Create Request** (Client only)
```
POST /api/requests/create
Authorization: Bearer {token}
Content-Type: application/json

{
  "contractor_id": 1,
  "category": "Plumbing",
  "title": "Fix kitchen sink",
  "description": "Need urgent plumbing repair...",
  "location": "Nairobi, Westlands"
}
```

**List Requests**
```
GET /api/requests/list
Authorization: Bearer {token}
```

**Respond to Request** (Contractor only)
```
POST /api/requests/respond
Authorization: Bearer {token}
Content-Type: application/json

{
  "request_id": 1,
  "action": "accept" // or "reject"
}
```

### Notifications

**Get Notifications**
```
GET /api/notifications/get
Authorization: Bearer {token}
```

**Mark as Read**
```
POST /api/notifications/get
Authorization: Bearer {token}
Content-Type: application/json

{
  "notification_id": 1  // or omit to mark all as read
}
```

### Categories

**List Categories**
```
GET /api/categories/list
```

## 🔐 Authentication

Include JWT token in requests:
```
Authorization: Bearer YOUR_JWT_TOKEN
```

## 🎯 Frontend Integration

Update your React frontend API URL:

In Vercel environment variables:
```
REACT_APP_API_URL=https://your-backend-url.railway.app/api
```

## 🧪 Testing

Use Postman or curl:

```bash
# Test API
curl https://your-api-url.com/

# Login
curl -X POST https://your-api-url.com/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@buildconnect.com","password":"admin123"}'

# Get contractors
curl https://your-api-url.com/api/contractors/list
```

## 📝 Notes

- Change default admin password in production
- Update `JWT_SECRET` in production
- Set proper file permissions on uploads directory
- Enable HTTPS in production
- Monitor upload directory size

## 🆘 Troubleshooting

**Database connection failed**
- Check DB credentials in `config/database.php`
- Verify MySQL service is running
- Check network access

**File upload errors**
- Verify `uploads/` directory exists
- Check directory permissions (777)
- Increase PHP upload limits in `.htaccess`

**CORS errors**
- Verify `.htaccess` CORS headers
- Check server Apache modules (mod_headers, mod_rewrite)

## 📞 Support

For issues, check:
1. PHP error logs
2. MySQL error logs
3. Browser console for CORS errors

## ✅ Production Checklist

- [ ] Change admin password
- [ ] Update JWT_SECRET
- [ ] Configure MySQL credentials
- [ ] Set proper file permissions
- [ ] Enable HTTPS
- [ ] Configure backups
- [ ] Monitor disk space
- [ ] Set up error logging
- [ ] Test all endpoints

---

**Built for BuildConnect - Professional Contractor Marketplace**
