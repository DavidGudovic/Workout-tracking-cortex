.PHONY: docker-up docker-down migrate-fresh-seed

docker-up:
	docker compose -f deployment/docker-compose.yml up -d

docker-down:
	docker compose -f deployment/docker-compose.yml down

migrate-fresh-seed:
	docker exec -it workouts-api php artisan migrate:fresh --seed




