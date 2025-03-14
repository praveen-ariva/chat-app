#!/bin/bash

# Chat API Test Script using curl
# This script performs tests on all endpoints of the Chat API
# Run it with: bash api_test.sh

BASE_URL="http://localhost:8080"
USER_ID=""
USER2_ID=""
GROUP_ID=""

# Text color variables
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[0;33m'
NC='\033[0m' # No Color

# Helper function to print test results
function test_result {
    if [ "$1" -eq 0 ]; then
        echo -e "${GREEN}PASSED${NC}"
    else
        echo -e "${RED}FAILED${NC} - $2"
    fi
}

echo -e "${YELLOW}===================================${NC}"
echo -e "${YELLOW}   CHAT API TESTING WITH CURL      ${NC}"
echo -e "${YELLOW}===================================${NC}"

# 1. User API Tests
echo -e "\n${YELLOW}1. USER API TESTS${NC}"
echo -e "${YELLOW}----------------${NC}"

# 1.1 Create a new user
echo -n "Test 1.1: Create a new user... "
RESPONSE=$(curl -s -w "%{http_code}" -X POST -H "Content-Type: application/json" -d '{"username":"testuser'$RANDOM'"}' $BASE_URL/users)
STATUS_CODE=${RESPONSE: -3}
BODY=${RESPONSE:0:${#RESPONSE}-3}

if [ "$STATUS_CODE" -eq 201 ]; then
    USER_ID=$(echo $BODY | sed -n 's/.*"id":\([0-9]*\).*/\1/p')
    test_result 0
    echo "Created user with ID: $USER_ID"
else
    test_result 1 "Status code: $STATUS_CODE, Response: $BODY"
fi

# 1.2 Create a second user
echo -n "Test 1.2: Create a second user... "
RESPONSE=$(curl -s -w "%{http_code}" -X POST -H "Content-Type: application/json" -d '{"username":"testuser'$RANDOM'"}' $BASE_URL/users)
STATUS_CODE=${RESPONSE: -3}
BODY=${RESPONSE:0:${#RESPONSE}-3}

if [ "$STATUS_CODE" -eq 201 ]; then
    USER2_ID=$(echo $BODY | sed -n 's/.*"id":\([0-9]*\).*/\1/p')
    test_result 0
    echo "Created second user with ID: $USER2_ID"
else
    test_result 1 "Status code: $STATUS_CODE, Response: $BODY"
fi

# 1.3 Create a user with missing username
echo -n "Test 1.3: Create a user with missing username... "
RESPONSE=$(curl -s -w "%{http_code}" -X POST -H "Content-Type: application/json" -d '{}' $BASE_URL/users)
STATUS_CODE=${RESPONSE: -3}

if [ "$STATUS_CODE" -eq 400 ]; then
    test_result 0
else
    test_result 1 "Status code: $STATUS_CODE"
fi

# 1.4 Get user
echo -n "Test 1.4: Get user... "
RESPONSE=$(curl -s -w "%{http_code}" -X GET $BASE_URL/users/$USER_ID)
STATUS_CODE=${RESPONSE: -3}

if [ "$STATUS_CODE" -eq 200 ]; then
    test_result 0
else
    test_result 1 "Status code: $STATUS_CODE"
fi

# 1.5 Get non-existing user
echo -n "Test 1.5: Get non-existing user... "
RESPONSE=$(curl -s -w "%{http_code}" -X GET $BASE_URL/users/99999)
STATUS_CODE=${RESPONSE: -3}

if [ "$STATUS_CODE" -eq 404 ]; then
    test_result 0
else
    test_result 1 "Status code: $STATUS_CODE"
fi

# 2. Group API Tests
echo -e "\n${YELLOW}2. GROUP API TESTS${NC}"
echo -e "${YELLOW}------------------${NC}"

# 2.1 Create a new group
echo -n "Test 2.1: Create a new group... "
RESPONSE=$(curl -s -w "%{http_code}" -X POST -H "Content-Type: application/json" -d '{"name":"testgroup'$RANDOM'","user_id":'$USER_ID'}' $BASE_URL/groups)
STATUS_CODE=${RESPONSE: -3}
BODY=${RESPONSE:0:${#RESPONSE}-3}

if [ "$STATUS_CODE" -eq 201 ]; then
    GROUP_ID=$(echo $BODY | sed -n 's/.*"id":\([0-9]*\).*/\1/p')
    test_result 0
    echo "Created group with ID: $GROUP_ID"
else
    test_result 1 "Status code: $STATUS_CODE, Response: $BODY"
fi

# 2.2 Create a group with missing name
echo -n "Test 2.2: Create a group with missing name... "
RESPONSE=$(curl -s -w "%{http_code}" -X POST -H "Content-Type: application/json" -d '{"user_id":'$USER_ID'}' $BASE_URL/groups)
STATUS_CODE=${RESPONSE: -3}

if [ "$STATUS_CODE" -eq 400 ]; then
    test_result 0
else
    test_result 1 "Status code: $STATUS_CODE"
fi

# 2.3 Create a group with missing user_id
echo -n "Test 2.3: Create a group with missing user_id... "
RESPONSE=$(curl -s -w "%{http_code}" -X POST -H "Content-Type: application/json" -d '{"name":"testgroup'$RANDOM'"}' $BASE_URL/groups)
STATUS_CODE=${RESPONSE: -3}

if [ "$STATUS_CODE" -eq 400 ]; then
    test_result 0
else
    test_result 1 "Status code: $STATUS_CODE"
fi

# 2.4 Get all groups
echo -n "Test 2.4: Get all groups... "
RESPONSE=$(curl -s -w "%{http_code}" -X GET $BASE_URL/groups)
STATUS_CODE=${RESPONSE: -3}

if [ "$STATUS_CODE" -eq 200 ]; then
    test_result 0
else
    test_result 1 "Status code: $STATUS_CODE"
fi

# 2.5 Join a group
echo -n "Test 2.5: Join a group... "
RESPONSE=$(curl -s -w "%{http_code}" -X POST -H "Content-Type: application/json" -d '{"user_id":'$USER2_ID'}' $BASE_URL/groups/$GROUP_ID/join)
STATUS_CODE=${RESPONSE: -3}

if [ "$STATUS_CODE" -eq 200 ]; then
    test_result 0
else
    test_result 1 "Status code: $STATUS_CODE"
fi

# 2.6 Join a non-existing group
echo -n "Test 2.6: Join a non-existing group... "
RESPONSE=$(curl -s -w "%{http_code}" -X POST -H "Content-Type: application/json" -d '{"user_id":'$USER2_ID'}' $BASE_URL/groups/99999/join)
STATUS_CODE=${RESPONSE: -3}

if [ "$STATUS_CODE" -eq 404 ]; then
    test_result 0
else
    test_result 1 "Status code: $STATUS_CODE"
fi

# 3. Message API Tests
echo -e "\n${YELLOW}3. MESSAGE API TESTS${NC}"
echo -e "${YELLOW}-------------------${NC}"

# 3.1 Send a message as first user
echo -n "Test 3.1: Send a message as first user... "
RESPONSE=$(curl -s -w "%{http_code}" -X POST -H "Content-Type: application/json" -d '{"user_id":'$USER_ID',"group_id":'$GROUP_ID',"content":"Hello from user 1"}' $BASE_URL/messages)
STATUS_CODE=${RESPONSE: -3}

if [ "$STATUS_CODE" -eq 201 ]; then
    test_result 0
else
    test_result 1 "Status code: $STATUS_CODE"
fi

# 3.2 Send a message as second user
echo -n "Test 3.2: Send a message as second user... "
RESPONSE=$(curl -s -w "%{http_code}" -X POST -H "Content-Type: application/json" -d '{"user_id":'$USER2_ID',"group_id":'$GROUP_ID',"content":"Hello from user 2"}' $BASE_URL/messages)
STATUS_CODE=${RESPONSE: -3}

if [ "$STATUS_CODE" -eq 201 ]; then
    test_result 0
else
    test_result 1 "Status code: $STATUS_CODE"
fi

# 3.3 Send a message with missing user_id
echo -n "Test 3.3: Send a message with missing user_id... "
RESPONSE=$(curl -s -w "%{http_code}" -X POST -H "Content-Type: application/json" -d '{"group_id":'$GROUP_ID',"content":"Missing user_id"}' $BASE_URL/messages)
STATUS_CODE=${RESPONSE: -3}

if [ "$STATUS_CODE" -eq 400 ]; then
    test_result 0
else
    test_result 1 "Status code: $STATUS_CODE"
fi

# 3.4 Send a message with missing group_id
echo -n "Test 3.4: Send a message with missing group_id... "
RESPONSE=$(curl -s -w "%{http_code}" -X POST -H "Content-Type: application/json" -d '{"user_id":'$USER_ID',"content":"Missing group_id"}' $BASE_URL/messages)
STATUS_CODE=${RESPONSE: -3}

if [ "$STATUS_CODE" -eq 400 ]; then
    test_result 0
else
    test_result 1 "Status code: $STATUS_CODE"
fi

# 3.5 Send a message with missing content
echo -n "Test 3.5: Send a message with missing content... "
RESPONSE=$(curl -s -w "%{http_code}" -X POST -H "Content-Type: application/json" -d '{"user_id":'$USER_ID',"group_id":'$GROUP_ID'}' $BASE_URL/messages)
STATUS_CODE=${RESPONSE: -3}

if [ "$STATUS_CODE" -eq 400 ]; then
    test_result 0
else
    test_result 1 "Status code: $STATUS_CODE"
fi

# 3.6 Get messages from a group
echo -n "Test 3.6: Get messages from a group... "
RESPONSE=$(curl -s -w "%{http_code}" -X GET $BASE_URL/groups/$GROUP_ID/messages)
STATUS_CODE=${RESPONSE: -3}

if [ "$STATUS_CODE" -eq 200 ]; then
    test_result 0
else
    test_result 1 "Status code: $STATUS_CODE"
fi

# 3.7 Get messages from a non-existing group
echo -n "Test 3.7: Get messages from a non-existing group... "
RESPONSE=$(curl -s -w "%{http_code}" -X GET $BASE_URL/groups/99999/messages)
STATUS_CODE=${RESPONSE: -3}

if [ "$STATUS_CODE" -eq 404 ]; then
    test_result 0
else
    test_result 1 "Status code: $STATUS_CODE"
fi

# Create a user who is not a member of any group
echo -n "Creating a non-member user for testing... "
RESPONSE=$(curl -s -w "%{http_code}" -X POST -H "Content-Type: application/json" -d '{"username":"nonmember'$RANDOM'"}' $BASE_URL/users)
STATUS_CODE=${RESPONSE: -3}
BODY=${RESPONSE:0:${#RESPONSE}-3}

if [ "$STATUS_CODE" -eq 201 ]; then
    NON_MEMBER_ID=$(echo $BODY | sed -n 's/.*"id":\([0-9]*\).*/\1/p')
    echo "Created non-member user with ID: $NON_MEMBER_ID"
else
    echo "Failed to create non-member user"
    NON_MEMBER_ID=$USER_ID # Fallback
fi

# 3.8 Send a message as a non-member
echo -n "Test 3.8: Send a message as a non-member... "
RESPONSE=$(curl -s -w "%{http_code}" -X POST -H "Content-Type: application/json" -d '{"user_id":'$NON_MEMBER_ID',"group_id":'$GROUP_ID',"content":"This should fail"}' $BASE_URL/messages)
STATUS_CODE=${RESPONSE: -3}

if [ "$STATUS_CODE" -eq 403 ]; then
    test_result 0
else
    test_result 1 "Status code: $STATUS_CODE (expected 403)"
fi

echo -e "\n${YELLOW}===================================${NC}"
echo -e "${YELLOW}   TEST SUMMARY                    ${NC}"
echo -e "${YELLOW}===================================${NC}"
echo "Total tests: 18"
echo "User API tests: 5"
echo "Group API tests: 6"
echo "Message API tests: 7"
echo -e "${YELLOW}===================================${NC}"
echo -e "Run server with: ${GREEN}php -S localhost:8080 -t public${NC}"
echo -e "${YELLOW}===================================${NC}"