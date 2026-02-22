#!/bin/bash
# Quick verification script for EduTrail backend fixes

echo "=== EduTrail Backend Verification Script ==="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

PROJECT_DIR="c:\CSC 120 PROJECT\edutrail-backend"

# Check if in project directory
if [ ! -f "$PROJECT_DIR/artisan" ]; then
    echo -e "${RED}Error: Not in Laravel project directory${NC}"
    echo "Expected artisan file at: $PROJECT_DIR/artisan"
    exit 1
fi

echo -e "${YELLOW}1. Checking PHP version...${NC}"
php -v | head -1

echo ""
echo -e "${YELLOW}2. Checking composer dependencies...${NC}"
if [ -d "$PROJECT_DIR/vendor" ]; then
    echo -e "${GREEN}✓ vendor/ exists${NC}"
else
    echo -e "${RED}✗ vendor/ not found. Run: composer install${NC}"
fi

echo ""
echo -e "${YELLOW}3. Checking .env file...${NC}"
if [ -f "$PROJECT_DIR/.env" ]; then
    echo -e "${GREEN}✓ .env exists${NC}"
    if grep -q "APP_KEY=base64:" "$PROJECT_DIR/.env"; then
        echo -e "${GREEN}✓ APP_KEY is set${NC}"
    else
        echo -e "${RED}⚠ APP_KEY may not be set${NC}"
    fi
else
    echo -e "${RED}✗ .env file not found${NC}"
fi

echo ""
echo -e "${YELLOW}4. Checking database migrations...${NC}"
if [ -f "$PROJECT_DIR/database/migrations/2026_02_11_150853_create_projects_table.php" ]; then
    echo -e "${GREEN}✓ Projects migration exists${NC}"
else
    echo -e "${RED}✗ Projects migration not found${NC}"
fi

echo ""
echo -e "${YELLOW}5. Checking key files are in place...${NC}"

FILES=(
    "app/Http/Controllers/ProjectController.php"
    "app/Http/Controllers/StorageController.php"
    "app/Http/Controllers/DebugController.php"
    "app/Models/Project.php"
    "routes/api.php"
)

for file in "${FILES[@]}"; do
    if [ -f "$PROJECT_DIR/$file" ]; then
        echo -e "${GREEN}✓ $file${NC}"
    else
        echo -e "${RED}✗ $file NOT FOUND${NC}"
    fi
done

echo ""
echo -e "${YELLOW}6. Quick lint check...${NC}"
php -l "$PROJECT_DIR/app/Http/Controllers/ProjectController.php" | grep -q "No syntax errors" && echo -e "${GREEN}✓ ProjectController.php syntax OK${NC}" || echo -e "${RED}✗ Syntax error in ProjectController.php${NC}"

echo ""
echo -e "${YELLOW}=== Pre-flight Checks Complete ===${NC}"
echo ""
echo -e "${GREEN}Next steps:${NC}"
echo "1. Verify database: mysql -u root (or your DB user)"
echo "2. Run migrations: php artisan migrate --force"
echo "3. Create storage link: php artisan storage:link"
echo "4. Start server: php artisan serve"
echo "5. Test endpoints with curl (see DEBUGGING_GUIDE.md)"
echo ""
echo "For full verification steps, see: FIX_SUMMARY.md"
