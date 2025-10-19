#!/bin/bash

# Test Authentication Features
# This script tests login, session management, password change, and user admin features

BASE_URL="http://localhost:8080"
COOKIE_FILE="test_cookies.txt"

echo "================================"
echo "Authentication Feature Tests"
echo "================================"
echo ""

# Clean up any existing cookie file
rm -f $COOKIE_FILE

# Test 1: Check initial session (should not be logged in)
echo "Test 1: Check initial session (not logged in)"
response=$(curl -s -c $COOKIE_FILE "${BASE_URL}/session_api.php?action=me")
echo "Response: $response"
if echo "$response" | grep -q '"success":false'; then
    echo "✅ Test 1 PASSED: Not logged in initially"
else
    echo "❌ Test 1 FAILED"
fi
echo ""

# Test 2: Login with admin credentials
echo "Test 2: Login with admin/admin123"
response=$(curl -s -b $COOKIE_FILE -c $COOKIE_FILE -X POST \
    -d "action=login" \
    -d "username=admin" \
    -d "password=admin123" \
    "${BASE_URL}/session_api.php")
echo "Response: $response"
if echo "$response" | grep -q '"success":true'; then
    echo "✅ Test 2 PASSED: Login successful"
else
    echo "❌ Test 2 FAILED"
    exit 1
fi
echo ""

# Test 3: Check session after login (should be logged in)
echo "Test 3: Check session after login"
response=$(curl -s -b $COOKIE_FILE "${BASE_URL}/session_api.php?action=me")
echo "Response: $response"
if echo "$response" | grep -q '"username":"admin"'; then
    echo "✅ Test 3 PASSED: Session active with admin user"
else
    echo "❌ Test 3 FAILED"
fi
echo ""

# Test 4: Try to save a configuration (should work now)
echo "Test 4: Save configuration while authenticated"
response=$(curl -s -b $COOKIE_FILE -c $COOKIE_FILE -X POST \
    -H "Content-Type: application/json" \
    -d '{"action":"save","siteName":"TEST_AUTH","adminNumber":"999","networkAddress":"192.168.1.0/24","networkData":{"division":"A","vlans":"TestVLAN","vlanids":"100"}}' \
    "${BASE_URL}/api.php")
echo "Response: $response"
if echo "$response" | grep -q '"success":true'; then
    echo "✅ Test 4 PASSED: Configuration saved while authenticated"
else
    echo "❌ Test 4 FAILED"
fi
echo ""

# Test 5: List users (admin only)
echo "Test 5: List users (admin privilege)"
response=$(curl -s -b $COOKIE_FILE "${BASE_URL}/session_api.php?action=list_users")
echo "Response: $response"
if echo "$response" | grep -q '"username":"admin"'; then
    echo "✅ Test 5 PASSED: User list retrieved"
else
    echo "❌ Test 5 FAILED"
fi
echo ""

# Test 6: Add a new test user (admin only)
echo "Test 6: Add new user 'testuser' (admin privilege)"
response=$(curl -s -b $COOKIE_FILE -X POST \
    -d "action=add_user" \
    -d "username=testuser" \
    -d "password=testpass123" \
    -d "role=user" \
    "${BASE_URL}/session_api.php")
echo "Response: $response"
if echo "$response" | grep -q '"success":true'; then
    echo "✅ Test 6 PASSED: New user added"
else
    echo "❌ Test 6 FAILED"
fi
echo ""

# Test 7: Logout
echo "Test 7: Logout"
response=$(curl -s -b $COOKIE_FILE "${BASE_URL}/session_api.php?action=logout")
echo "Response: $response"
if echo "$response" | grep -q '"success":true'; then
    echo "✅ Test 7 PASSED: Logout successful"
else
    echo "❌ Test 7 FAILED"
fi
echo ""

# Test 8: Verify session cleared after logout
echo "Test 8: Check session after logout"
response=$(curl -s -b $COOKIE_FILE "${BASE_URL}/session_api.php?action=me")
echo "Response: $response"
if echo "$response" | grep -q '"success":false'; then
    echo "✅ Test 8 PASSED: Session cleared after logout"
else
    echo "❌ Test 8 FAILED"
fi
echo ""

# Test 9: Login with new test user
echo "Test 9: Login with testuser/testpass123"
response=$(curl -s -b $COOKIE_FILE -c $COOKIE_FILE -X POST \
    -d "action=login" \
    -d "username=testuser" \
    -d "password=testpass123" \
    "${BASE_URL}/session_api.php")
echo "Response: $response"
if echo "$response" | grep -q '"success":true'; then
    echo "✅ Test 9 PASSED: New user can login"
else
    echo "❌ Test 9 FAILED"
fi
echo ""

# Test 10: Change password for test user
echo "Test 10: Change password for testuser"
response=$(curl -s -b $COOKIE_FILE -X POST \
    -d "action=change_password" \
    -d "current_password=testpass123" \
    -d "new_password=newpass456" \
    "${BASE_URL}/session_api.php")
echo "Response: $response"
if echo "$response" | grep -q '"success":true'; then
    echo "✅ Test 10 PASSED: Password changed successfully"
else
    echo "❌ Test 10 FAILED"
fi
echo ""

# Test 11: Logout test user
echo "Test 11: Logout testuser"
curl -s -b $COOKIE_FILE "${BASE_URL}/session_api.php?action=logout" > /dev/null
echo ""

# Test 12: Login with new password
echo "Test 12: Login with testuser/newpass456 (new password)"
response=$(curl -s -b $COOKIE_FILE -c $COOKIE_FILE -X POST \
    -d "action=login" \
    -d "username=testuser" \
    -d "password=newpass456" \
    "${BASE_URL}/session_api.php")
echo "Response: $response"
if echo "$response" | grep -q '"success":true'; then
    echo "✅ Test 12 PASSED: Login with new password successful"
else
    echo "❌ Test 12 FAILED"
fi
echo ""

# Test 13: Regular user cannot list users
echo "Test 13: Regular user cannot list users"
response=$(curl -s -b $COOKIE_FILE "${BASE_URL}/session_api.php?action=list_users")
echo "Response: $response"
if echo "$response" | grep -q '"success":false'; then
    echo "✅ Test 13 PASSED: Regular user denied admin privilege"
else
    echo "❌ Test 13 FAILED"
fi
echo ""

# Cleanup: Login as admin and delete test user
echo "Cleanup: Deleting test user"
rm -f $COOKIE_FILE
curl -s -c $COOKIE_FILE -X POST \
    -d "action=login" \
    -d "username=admin" \
    -d "password=admin123" \
    "${BASE_URL}/session_api.php" > /dev/null

curl -s -b $COOKIE_FILE -X POST \
    -d "action=delete_user" \
    -d "username=testuser" \
    "${BASE_URL}/session_api.php" > /dev/null
echo "Cleanup completed"
echo ""

# Clean up cookie file
rm -f $COOKIE_FILE

echo "================================"
echo "All Authentication Tests Complete"
echo "================================"
