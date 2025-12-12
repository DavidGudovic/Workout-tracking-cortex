docker-up:
	docker compose -f deployment/docker-compose.yml up -d

docker-down:
	docker compose -f deployment/docker-compose.yml down
