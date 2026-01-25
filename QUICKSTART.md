# ⚡ Quick Start Guide

**Get BuildConnect backend running in 10 minutes!**

## 🎯 What You're Getting

- ✅ Complete PHP backend
- ✅ MySQL database schema
- ✅ RESTful API (15 endpoints)
- ✅ JWT authentication
- ✅ Role-based access
- ✅ File uploads
- ✅ Notifications
- ✅ Ready for deployment

## 📦 Files Included

```
buildconnect-php/
├── api/              # API endpoints
├── config/           # Database config
├── database/         # SQL schema
├── uploads/          # File storage
├── .htaccess         # Apache config
├── index.php         # Entry point
├── README.md         # Full documentation
├── RAILWAY_DEPLOY.md # Deployment guide
├── API_TESTING.md    # Testing guide
└── QUICKSTART.md     # This file
```

## 🚀 Fastest Deployment: Railway

### Step 1: Push to GitHub (2 mins)

```bash
cd buildconnect-php
git init
git add .
git commit -m "BuildConnect backend"
git remote add origin https://github.com/YOUR_USERNAME/buildconnect-backend.git
git push -u origin main
```

### Step 2: Deploy (5 mins)

1. Go to [railway.app](https://railway.app)
2. Login with GitHub
3. "New Project" → "Deploy from GitHub"
4. Select `buildconnect-backend`
5. Add MySQL database
6. Add environment variable: `JWT_SECRET=your-secret-key`
7. Done! Get your URL

### Step 3: Import Database (2 mins)

**Option A: Railway CLI**
```bash
npm install -g @railway/cli
railway login
railway link
railway run mysql -u root < database/schema.sql
```

**Option B: Copy-paste SQL**
- In Railway, click MySQL → Data tab
- Copy contents of `database/schema.sql`
- Paste and execute

### Step 4: Update Frontend (1 min)

In Vercel (buildconnect-ke):
1. Settings → Environment Variables
2. Update `REACT_APP_API_URL` = `https://your-railway-url.up.railway.app/api`
3. Redeploy

## ✅ Test It!

```bash
# Login as admin
curl -X POST https://your-railway-url.up.railway.app/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@buildconnect.com","password":"admin123"}'
```

**You should get a token!**

## 📱 Default Admin Account

```
Email: admin@buildconnect.com
Password: admin123
```

**⚠️ CHANGE THIS IN PRODUCTION!**

## 🎓 Full System Flow

1. **Contractor Registers** → Status: Pending
2. **Admin Verifies** → Status: Approved/Rejected
3. **Client Registers** → Active immediately
4. **Client Browses Contractors** → Only approved shown
5. **Client Sends Request** → Contractor notified
6. **Contractor Accepts/Rejects** → Client notified
7. **Direct Communication** → Via shared contacts

## 📚 Documentation

- **Full docs**: `README.md`
- **Deployment**: `RAILWAY_DEPLOY.md`
- **API testing**: `API_TESTING.md`

## 🆘 Quick Troubleshooting

**Database error?**
→ Import `database/schema.sql`

**CORS error?**
→ Check frontend API URL in Vercel env vars

**Login fails?**
→ Import database schema (includes admin user)

**Upload fails?**
→ Check `uploads/` directory exists & has permissions

## 🎯 Next Steps

1. ✅ Deploy backend
2. ✅ Update frontend API URL
3. ✅ Test login
4. ✅ Change admin password
5. ✅ Go live!

## 💡 Pro Tips

- Use Railway CLI for database access
- Monitor logs in Railway dashboard
- Test locally with XAMPP/MAMP first
- Keep `JWT_SECRET` secure
- Backup database regularly

---

**Need help?** Read the full `README.md` or `RAILWAY_DEPLOY.md`

**Ready to code?** Push to GitHub and deploy!

🚀 **Let's build something awesome!**
