.PHONY: docker-up docker-down migrate-fresh-seed

docker-up:
	docker compose -f deployment/docker-compose.yml up -d

docker-down:
	docker compose -f deployment/docker-compose.yml down

migrate-fresh-seed:
	docker exec -it workouts-api php artisan migrate:fresh --seed

laravel-cache:
	docker exec workouts-api php artisan config:cache
	docker exec workouts-api php artisan route:cache
	docker exec workouts-api php artisan view:cache

laravel-key:
	docker exec workouts-api php artisan key:generate

laravel-storage-link:
	docker exec workouts-api php artisan storage:link
