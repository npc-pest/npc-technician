# Payment Settings Debug Guide

## Current Issue
The API is returning: `{"status":"error","message":"Session ID is required"}`

## Debug Steps

### 1. Check User Login Status
Open browser console and run:
```javascript
console.log('Session ID:', localStorage.getItem('sessionId'));
console.log('User Role:', localStorage.getItem('userRole'));
console.log('Session Expiry:', localStorage.getItem('sessionExpiry'));
```

### 2. Test API Endpoint Directly
```bash
# Test without session ID
curl -X GET "https://npcpest.com/npc/get_payment_settings.php" \
  -H "Content-Type: application/json"

# Test with session ID (if you have one)
curl -X GET "https://npcpest.com/npc/get_payment_settings.php" \
  -H "Content-Type: application/json" \
  -H "Session-ID: YOUR_SESSION_ID"
```

### 3. Check Admin Login
1. Go to the login page
2. Login as admin with correct credentials
3. Check if session ID is stored in localStorage
4. Try accessing the Payment Settings page again

### 4. Verify Database
Check if the payment_settings table exists and has data:
```sql
SELECT * FROM payment_settings WHERE id = 1;
```

### 5. Check Server Logs
Look for any server-side errors in the PHP error logs.

## Expected Behavior

### For Admin Users:
- Should be able to access Payment Settings page
- Should see toggle switches for payment methods
- Should be able to save changes

### For Regular Users:
- Should see payment options on service booking page
- Should not be able to access admin Payment Settings

### For Non-Logged Users:
- Should see default payment options (both enabled)
- Should not be able to access admin Payment Settings

## Troubleshooting

### If Session ID is missing:
1. Log out and log back in as admin
2. Check if the login response includes sessionid
3. Verify localStorage is working

### If Session ID exists but still getting error:
1. Check if session has expired
2. Verify the session ID format
3. Check server-side session validation

### If API endpoint is not accessible:
1. Check server configuration
2. Verify file permissions
3. Check for any middleware requiring authentication

## Quick Fix
If the issue persists, you can temporarily modify the frontend to handle the error gracefully:

```javascript
// In ServiceDetails.tsx - fetchPaymentSettings function
catch (error: any) {
  console.error('Error fetching payment settings:', error);
  // Default to both payment methods enabled
  setPaymentSettings({
    cash_on_delivery_enabled: true,
    online_payment_enabled: true
  });
  setAvailablePaymentMethods(['cash', 'online']);
}
```

This will ensure users can still book services even if the payment settings API fails. 