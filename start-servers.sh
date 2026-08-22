#!/bin/bash
# Start the Church Command Center backend servers
# Usage: ./start-servers.sh

cd "$(dirname "$0")"

echo "🚀 Starting Church Command Center servers..."

# Start Laravel API server
echo "📡 Starting API server on port 8000..."
php artisan serve --port=8000 &
API_PID=$!

# Start Reverb WebSocket server
echo "🔌 Starting Reverb WebSocket server on port 8080..."
php artisan reverb:start --port=8080 &
REVERB_PID=$!

# Start queue worker
echo "📋 Starting queue worker..."
php artisan queue:work --tries=3 &
QUEUE_PID=$!

echo ""
echo "✅ All servers started!"
echo "   API:      http://localhost:8000 (PID: $API_PID)"
echo "   WebSocket: ws://localhost:8080 (PID: $REVERB_PID)"
echo "   Queue:     worker (PID: $QUEUE_PID)"
echo ""
echo "Press Ctrl+C to stop all servers"

# Wait for any child to exit
wait
