# Payment Settings Testing Guide

## Overview
This guide helps you test the admin-controlled payment options implementation in the NPC website.

## Prerequisites
1. Database setup completed (payment_settings table created)
2. Backend APIs are accessible
3. Website is running

## Database Setup
Run the SQL script to create the payment_settings table:
```sql
-- Run this in your database
CREATE TABLE `payment_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cash_on_delivery_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `online_payment_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default settings
INSERT INTO `payment_settings` (`id`, `cash_on_delivery_enabled`, `online_payment_enabled`, `updated_by`) 
VALUES (1, 1, 1, 1);
```

## API Testing

### 1. Test Get Payment Settings
```bash
curl -X GET "https://npcpest.com/npc/get_payment_settings.php" \
  -H "Content-Type: application/json"
```

Expected Response:
```json
{
  "status": "success",
  "payment_settings": {
    "cash_on_delivery_enabled": true,
    "online_payment_enabled": true
  }
}
```

### 2. Test Update Payment Settings (Admin Only)
```bash
curl -X POST "https://npcpest.com/npc/update_payment_settings.php" \
  -H "Content-Type: application/json" \
  -H "Session-ID: YOUR_ADMIN_SESSION_ID" \
  -d '{
    "cash_on_delivery_enabled": false,
    "online_payment_enabled": true
  }'
```

## Website Testing

### 1. Admin Dashboard Testing

**Step 1: Access Payment Settings**
1. Login as admin
2. Navigate to "Payment Settings" tab
3. Verify both payment methods are enabled by default

**Step 2: Test Toggle Functionality**
1. Toggle "Cash on Delivery" to disabled
2. Click "Save Settings"
3. Verify success message appears
4. Refresh page and verify setting persists

**Step 3: Test Warning Message**
1. Disable both payment methods
2. Verify warning message appears
3. Re-enable at least one method

### 2. User Booking Testing

**Step 1: Test with Both Methods Enabled**
1. Login as user
2. Navigate to any service booking page
3. Verify both payment options are visible
4. Select "Cash on Delivery" and proceed
5. Verify booking completes successfully

**Step 2: Test with Only Cash Enabled**
1. In admin panel, disable "Online Payment"
2. As user, try to book a service
3. Verify only "Cash on Delivery" option appears
4. Complete booking successfully

**Step 3: Test with Only Online Payment Enabled**
1. In admin panel, disable "Cash on Delivery", enable "Online Payment"
2. As user, try to book a service
3. Verify only "Online Payment" option appears
4. Complete booking with Razorpay

**Step 4: Test with No Payment Methods**
1. In admin panel, disable both payment methods
2. As user, try to book a service
3. Verify error message appears: "No payment methods are currently available"
4. Verify booking is blocked

### 3. Edge Cases Testing

**Test 1: Session Expiry**
1. Let admin session expire
2. Try to update payment settings
3. Verify appropriate error message

**Test 2: Network Errors**
1. Disconnect internet
2. Try to fetch/update payment settings
3. Verify error handling works

**Test 3: Invalid Data**
1. Try to update with invalid JSON
2. Verify validation works

## Expected Behaviors

### Admin Panel
- ✅ Payment settings load correctly
- ✅ Toggle switches work smoothly
- ✅ Save button shows loading state
- ✅ Success/error messages appear
- ✅ Warning shows when both methods disabled
- ✅ Settings persist after page refresh

### User Booking
- ✅ Only enabled payment methods show
- ✅ Default payment method is first available option
- ✅ Booking blocked if no payment methods available
- ✅ Error messages are clear and helpful
- ✅ Payment flow works for each enabled method

### API Responses
- ✅ GET returns current settings
- ✅ POST updates settings successfully
- ✅ Proper error handling for invalid requests
- ✅ Session validation works
- ✅ CORS headers are set correctly

## Troubleshooting

### Common Issues

1. **Payment settings not loading**
   - Check database connection
   - Verify payment_settings table exists
   - Check API endpoint URL

2. **Toggle switches not working**
   - Check browser console for errors
   - Verify API responses
   - Check network connectivity

3. **Settings not saving**
   - Verify admin session is valid
   - Check API endpoint permissions
   - Verify database write permissions

4. **User booking issues**
   - Check payment settings are loaded
   - Verify API calls are working
   - Check browser console for errors

### Debug Steps

1. Open browser developer tools
2. Check Network tab for API calls
3. Check Console tab for errors
4. Verify database state directly
5. Test API endpoints manually

## Success Criteria

- [ ] Admin can enable/disable payment methods
- [ ] Settings persist in database
- [ ] Users only see enabled payment methods
- [ ] Booking blocked when no methods available
- [ ] Error messages are clear and helpful
- [ ] All edge cases handled properly
- [ ] No console errors in browser
- [ ] API responses are consistent 