#!/bin/bash

# ADMS Server API Test Script
# This script tests all major API endpoints

API_KEY="change_this_in_production"
BASE_URL="http://localhost:8080"

echo "==============================================="
echo "ADMS Server API Test Script"
echo "==============================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to test endpoint
test_endpoint() {
    local name=$1
    local method=$2
    local endpoint=$3
    local data=$4
    
    echo -e "${YELLOW}Testing: $name${NC}"
    
    if [ -z "$data" ]; then
        response=$(curl -s -X $method -H "X-API-Key: $API_KEY" "$BASE_URL$endpoint")
    else
        response=$(curl -s -X $method -H "X-API-Key: $API_KEY" -H "Content-Type: application/json" -d "$data" "$BASE_URL$endpoint")
    fi
    
    # Check if response contains success: true or is successful
    if echo "$response" | grep -q '"success":true' || echo "$response" | grep -q '"status":"healthy"'; then
        echo -e "${GREEN}✓ PASSED${NC}"
        echo "Response: $response" | jq . 2>/dev/null || echo "$response"
    else
        echo -e "${RED}✗ FAILED${NC}"
        echo "Response: $response"
    fi
    echo ""
}

echo "1. Health Check"
test_endpoint "Health Check" "GET" "/api/health"

echo "2. Device Management"
test_endpoint "Register Device" "POST" "/api/devices/register" \
  '{"serial_number":"TEST_'$(date +%s)'","device_name":"Test Device","ip_address":"192.168.1.100","model":"ZKTeco K40"}'

test_endpoint "Get All Devices" "GET" "/api/devices"

test_endpoint "Get Device by ID" "GET" "/api/devices/1"

test_endpoint "Update Device" "PUT" "/api/devices/1" \
  '{"device_name":"Updated Device Name","firmware_version":"v2.4.1"}'

echo "3. Branch Management"
test_endpoint "Create Branch" "POST" "/api/branches" \
  '{"name":"Test Branch","code":"TB'$(date +%s)'","location":"Test Location"}'

test_endpoint "Get All Branches" "GET" "/api/branches"

test_endpoint "Assign Device to Branch" "POST" "/api/devices/1/assign-branch" \
  '{"branch_id":1}'

echo "4. Attendance Management"
test_endpoint "Create Attendance Record" "POST" "/api/attendance" \
  '{"device_id":1,"user_id":"EMP001","timestamp":"2024-01-15 09:00:00","verify_mode":1,"in_out_mode":0}'

test_endpoint "Get Attendance by Device" "GET" "/api/attendance/device/1"

test_endpoint "Get Attendance by User" "GET" "/api/attendance/user/EMP001"

echo "5. Administrative Endpoints"
test_endpoint "Get System Logs" "GET" "/api/admin/logs/system?limit=5"

test_endpoint "Get Device Logs" "GET" "/api/admin/logs/device/1?limit=5"

echo "6. Duplicate Prevention Test"
test_endpoint "Create Duplicate Attendance (should fail)" "POST" "/api/attendance" \
  '{"device_id":1,"user_id":"EMP001","timestamp":"2024-01-15 09:00:00","verify_mode":1,"in_out_mode":0}'

echo "==============================================="
echo "API Testing Complete"
echo "==============================================="
