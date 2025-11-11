#!/bin/bash

# Orange Money API - Production Build Script
# This script prepares the application for production deployment

set -e

echo "🚀 Starting Orange Money API production build..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if required tools are installed
check_dependencies() {
    print_status "Checking dependencies..."

    if ! command -v docker &> /dev/null; then
        print_error "Docker is not installed. Please install Docker first."
        exit 1
    fi

    if ! command -v docker-compose &> /dev/null; then
        print_error "Docker Compose is not installed. Please install Docker Compose first."
        exit 1
    fi

    print_success "Dependencies check passed"
}

# Clean up previous builds
cleanup() {
    print_status "Cleaning up previous builds..."

    # Remove old containers and images
    docker-compose down --volumes --remove-orphans 2>/dev/null || true
    docker system prune -f 2>/dev/null || true

    print_success "Cleanup completed"
}

# Build the application
build_app() {
    print_status "Building application..."

    # Build Docker images
    docker-compose build --no-cache

    print_success "Application built successfully"
}

# Run database migrations and seeders
setup_database() {
    print_status "Setting up database..."

    # Start only the database service
    docker-compose up -d db

    # Wait for database to be ready
    print_status "Waiting for database to be ready..."
    sleep 10

    # Run migrations
    docker-compose run --rm app php artisan migrate --force

    # Seed the database (optional)
    # docker-compose run --rm app php artisan db:seed --force

    print_success "Database setup completed"
}

# Generate application cache
generate_cache() {
    print_status "Generating application cache..."

    # Generate various caches
    docker-compose run --rm app php artisan config:cache
    docker-compose run --rm app php artisan route:cache
    docker-compose run --rm app php artisan view:cache

    # Generate Swagger documentation
    docker-compose run --rm app php artisan l5-swagger:generate

    print_success "Cache generation completed"
}

# Run tests
run_tests() {
    print_status "Running tests..."

    # Run PHP tests
    docker-compose run --rm app php artisan test

    print_success "Tests passed"
}

# Start the application
start_app() {
    print_status "Starting application..."

    # Start all services
    docker-compose up -d

    # Wait for application to be ready
    print_status "Waiting for application to be ready..."
    sleep 5

    # Check health endpoint
    if curl -f http://localhost/health > /dev/null 2>&1; then
        print_success "Application is healthy and running"
        print_success "API available at: http://localhost"
        print_success "Swagger docs at: http://localhost/api/docs"
    else
        print_error "Application health check failed"
        exit 1
    fi
}

# Show deployment information
show_info() {
    print_success "🎉 Deployment completed successfully!"
    echo ""
    echo "📋 Deployment Information:"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "🌐 API URL:         http://localhost"
    echo "📚 Swagger Docs:    http://localhost/api/docs"
    echo "🏥 Health Check:    http://localhost/health"
    echo "🐳 Docker Status:   $(docker-compose ps)"
    echo ""
    echo "🔧 Useful Commands:"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "View logs:          docker-compose logs -f"
    echo "Stop services:      docker-compose down"
    echo "Restart services:   docker-compose restart"
    echo "Run tests:          docker-compose run --rm app php artisan test"
    echo "Run migrations:     docker-compose run --rm app php artisan migrate"
    echo ""
    echo "🔐 Environment Variables:"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "Make sure to set the following environment variables:"
    echo "- APP_KEY:          Laravel application key"
    echo "- DB_HOST:          Database host"
    echo "- DB_DATABASE:      Database name"
    echo "- DB_USERNAME:      Database username"
    echo "- DB_PASSWORD:      Database password"
    echo "- REDIS_HOST:       Redis host"
    echo "- BREVO_API_KEY:    Brevo/Sendinblue API key"
    echo "- TWILIO_SID:       Twilio SID"
    echo "- TWILIO_TOKEN:     Twilio token"
    echo "- TWILIO_PHONE_NUMBER: Twilio phone number"
    echo ""
}

# Main deployment process
main() {
    echo "🏦 Orange Money API - Production Deployment"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""

    check_dependencies
    cleanup
    build_app
    setup_database
    generate_cache

    # Run tests (uncomment if you want to run tests during deployment)
    # run_tests

    start_app
    show_info
}

# Handle command line arguments
case "${1:-}" in
    "build")
        check_dependencies
        build_app
        ;;
    "setup-db")
        check_dependencies
        setup_database
        ;;
    "cache")
        check_dependencies
        generate_cache
        ;;
    "test")
        check_dependencies
        run_tests
        ;;
    "start")
        check_dependencies
        start_app
        ;;
    "cleanup")
        cleanup
        ;;
    *)
        main
        ;;
esac
