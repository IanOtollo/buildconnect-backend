# 🚂 Deploy to Railway - Step by Step

## Prerequisites
- GitHub account
- Railway account (free): https://railway.app

## Step 1: Push to GitHub (5 minutes)

```bash
cd buildconnect-php

# Initialize git
git init

# Add all files
git add .

# Commit
git commit -m "Initial BuildConnect PHP backend"

# Add remote (replace with your repo URL)
git remote add origin https://github.com/YOUR_USERNAME/buildconnect-backend.git

# Push
git branch -M main
git push -u origin main
```

## Step 2: Deploy on Railway (10 minutes)

### 2.1 Create New Project

1. Go to [railway.app](https://railway.app)
2. Click "Login" → Sign in with GitHub
3. Click "New Project"
4. Select "Deploy from GitHub repo"
5. Choose `buildconnect-backend` repository
6. Click "Deploy Now"

### 2.2 Add MySQL Database

1. In your Railway project dashboard
2. Click "New" → "Database" → "Add MySQL"
3. Railway automatically creates the database
4. Database credentials are auto-configured!

### 2.3 Set Environment Variables

1. Click on your PHP service (not the database)
2. Go to "Variables" tab
3. Click "New Variable"
4. Add:

```
JWT_SECRET=your-super-secret-key-change-this-in-production
```

**That's it!** Railway automatically configures `DATABASE_URL` from MySQL service.

### 2.4 Get Your API URL

1. Go to "Settings" tab
2. Scroll to "Domains"
3. Click "Generate Domain"
4. Copy the URL (e.g., `https://buildconnect-backend-production.up.railway.app`)

## Step 3: Import Database Schema

### Option A: Railway CLI (Recommended)

1. Install Railway CLI:
```bash
npm install -g @railway/cli
```

2. Login:
```bash
railway login
```

3. Link to project:
```bash
railway link
```

4. Import schema:
```bash
railway run mysql -u root < database/schema.sql
```

### Option B: phpMyAdmin (If available)

1. In Railway dashboard, click on MySQL service
2. Note connection details
3. Use phpMyAdmin or MySQL Workbench
4. Import `database/schema.sql`

### Option C: Manual SQL Execution

1. In Railway dashboard, click MySQL service
2. Go to "Data" tab
3. Copy contents of `database/schema.sql`
4. Paste and execute

## Step 4: Update Frontend

1. Go to Vercel dashboard
2. Select `buildconnect-ke` project
3. Settings → Environment Variables
4. Add/Update:

```
REACT_APP_API_URL=https://your-railway-url.up.railway.app/api
```

(Replace with your actual Railway URL)

5. Deployments → Redeploy

## Step 5: Test

Test your API:

```bash
# Test API root
curl https://your-railway-url.up.railway.app/

# Test login
curl -X POST https://your-railway-url.up.railway.app/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@buildconnect.com","password":"admin123"}'
```

## 🎉 Done!

Your backend is now live at:
```
https://your-railway-url.up.railway.app
```

## 🔧 Troubleshooting

**"Database connection failed"**
- Make sure MySQL service is running
- Check that both services are in same project
- Railway auto-configures DATABASE_URL

**"Upload errors"**
- Railway has ephemeral filesystem
- Consider using cloud storage (Cloudinary, AWS S3) for production
- Or use Railway volumes

**"CORS errors"**
- Verify frontend URL in Vercel
- Check API URL in frontend env vars

## 📊 Monitor Your App

1. Railway dashboard → Your service
2. "Deployments" - see build logs
3. "Metrics" - monitor usage
4. "Logs" - debug errors

## 💰 Cost

Railway free tier includes:
- $5 credit/month
- Usually enough for small projects
- MySQL included in free tier

## 🔄 Auto-Deploy

Every time you push to GitHub:
```bash
git add .
git commit -m "Update feature"
git push
```

Railway automatically deploys! 🚀

---

**Need help?** Check Railway docs: https://docs.railway.app
