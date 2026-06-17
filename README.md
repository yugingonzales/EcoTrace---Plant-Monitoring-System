\# EcoTrace - Plant Monitoring System



A monorepo containing both \*\*Backend\*\* (Node.js/Next.js) and \*\*Frontend\*\* (Java/Kotlin Android) for the EcoTrace plant monitoring application.



\---



\## 📁 Repository Structure



```

EcoTrace/

├── backend/                    # Node.js/Next.js Backend API

│   ├── src/

│   │   ├── pages/api/         # Next.js API routes

│   │   ├── lib/               # Utility functions, DB, auth

│   │   ├── middleware/        # Authentication, validation

│   │   └── types/             # TypeScript types

│   ├── prisma/                # Database schema \& migrations

│   ├── .env.local             # Environment variables (NOT COMMITTED)

│   ├── package.json           # Backend dependencies

│   ├── tsconfig.json          # TypeScript config

│   └── README.md              # Backend-specific setup

│

├── frontend/                   # Java/Kotlin Android App

│   ├── app/                   # Android app module

│   │   ├── src/

│   │   │   ├── main/java/     # Java/Kotlin source code

│   │   │   ├── res/           # Resources (layouts, strings, etc.)

│   │   │   └── AndroidManifest.xml

│   │   └── build.gradle

│   ├── gradle/                # Gradle wrapper

│   ├── settings.gradle        # Project settings

│   ├── build.gradle           # Root build config

│   └── README.md              # Frontend-specific setup

│

├── docs/                       # Shared documentation

│   ├── API\_DOCUMENTATION.md   # Backend API specs

│   ├── DATABASE\_SCHEMA.md     # Database design

│   ├── SYSTEM\_ARCHITECTURE.md # Architecture diagrams

│   └── DEPLOYMENT.md          # Deployment guide

│

├── .github/

│   └── workflows/

│       ├── backend-ci.yml     # Backend tests \& linting

│       └── frontend-ci.yml    # Frontend build \& tests

│

├── .gitignore                 # Global git ignore rules

├── README.md                  # THIS FILE

└── CONTRIBUTING.md            # Contribution guidelines



```



\---



\## 🎯 Quick Start



\### Prerequisites

\- \*\*For Backend\*\*: Node.js 18+, npm/yarn, MySQL 8.0+

\- \*\*For Frontend\*\*: Android Studio, Java 11+, Gradle

\- \*\*For Both\*\*: Git, GitHub account



\### Backend Setup (You - Yugin)



```bash

cd backend



\# Copy environment variables

cp .env.example .env.local



\# Install dependencies

npm install



\# Set up database

npm run prisma:setup



\# Start development server

npm run dev



\# Server runs at: http://localhost:3000

```



See \[backend/README.md](./backend/README.md) for detailed setup.



\### Frontend Setup (Frontend Developer)



```bash

cd frontend



\# Open in Android Studio (File > Open > select frontend folder)



\# Or build from command line:

./gradlew build



\# Run on emulator:

./gradlew installDebug

```



See \[frontend/README.md](./frontend/README.md) for detailed setup.



\---



\## 🔄 Development Workflow



\### Backend Developer (You)

1\. Work in `/backend` folder

2\. Implement API endpoints following the API contract

3\. Run tests: `npm test`

4\. Push to feature branch: `git checkout -b feat/feature-name`



\### Frontend Developer

1\. Work in `/frontend` folder

2\. Implement UI/UX using API endpoints

3\. Run tests: `./gradlew test`

4\. Push to feature branch



\### Both Together

\- Pull latest `main` branch regularly

\- Discuss API contract changes before implementing

\- Test integration in staging environment before merging to `main`



\---



\## 📚 API Contract



The \*\*Frontend\*\* and \*\*Backend\*\* teams must agree on the API contract:



\### Example: GET /api/plants/nearby



\*\*Request:\*\*

```

GET /api/plants/nearby?latitude=12.533\&longitude=124.872\&radius=1\&limit=5\&status=all

```



\*\*Response:\*\*

```json

{

&#x20; "success": true,

&#x20; "count": 3,

&#x20; "plants": \[

&#x20;   {

&#x20;     "id": 1,

&#x20;     "latitude": 12.5336,

&#x20;     "longitude": 124.8726,

&#x20;     "locationAddress": "Zone 3 Poblacion",

&#x20;     "distance": 0.02,

&#x20;     "isVerified": false,

&#x20;     "isReserved": false,

&#x20;     "reservedBy": null

&#x20;   }

&#x20; ]

}

```



\*\*Full API documentation:\*\* See \[docs/API\_DOCUMENTATION.md](./docs/API\_DOCUMENTATION.md)



\---



\## 🗄️ Database Schema



\- \*\*Shared with EcoTag\*\*: `ecotag\_plants`, `ecotag\_students`, `ecotag\_events`

\- \*\*EcoTrace specific\*\*: `ecotrace\_tasks`, `ecotrace\_reservations`, `ecotrace\_verifications`, `ecotrace\_photos`



See \[docs/DATABASE\_SCHEMA.md](./docs/DATABASE\_SCHEMA.md) for complete schema.



\---



\## 🚀 Deployment



\### Backend Deployment

\- Server: University on-premises or self-hosted

\- Process Manager: PM2

\- Reverse Proxy: Nginx

\- Database: MySQL 8.0+



See \[docs/DEPLOYMENT.md](./docs/DEPLOYMENT.md)



\### Frontend Deployment

\- Platform: Google Play Store / F-Droid

\- Signing: App signing certificate

\- Versioning: Semantic versioning (v1.0.0)



\---



\## 🔄 CI/CD Pipeline



\### GitHub Actions Workflows



\*\*Backend Tests\*\* (`.github/workflows/backend-ci.yml`)

\- Runs on every push to `develop` and `main`

\- Linting: `npm run lint`

\- Tests: `npm test`

\- Build: `npm run build`



\*\*Frontend Build\*\* (`.github/workflows/frontend-ci.yml`)

\- Runs on every push to `develop` and `main`

\- Lint: `./gradlew lint`

\- Test: `./gradlew test`

\- Build: `./gradlew build`



Push to `main` requires all checks to pass.



\---



\## 📋 Git Workflow



```bash

\# 1. Create feature branch

git checkout -b feat/reservation-system



\# 2. Make changes in backend/ or frontend/

\# Backend example:

\# - backend/src/pages/api/reservations/index.ts



\# 3. Commit with clear message

git commit -m "feat: Add reservation lock system"



\# 4. Push to GitHub

git push origin feat/reservation-system



\# 5. Create Pull Request on GitHub

\# - Link to Trello/issue tracker

\# - Request review from team

\# - Wait for CI/CD to pass



\# 6. After review, merge to develop

\# 7. When ready, merge develop → main

```



\---



\## 📝 Naming Conventions



\### Branches

\- `main` - Production-ready code

\- `develop` - Development branch (test before merging to main)

\- `feat/feature-name` - New feature

\- `fix/bug-name` - Bug fix

\- `docs/update-readme` - Documentation



\### Commits

```

feat: Add new feature

fix: Fix bug

docs: Update documentation

refactor: Refactor code

test: Add tests

chore: Update dependencies

```



\### Files

\- \*\*Backend\*\*: camelCase for functions/variables, PascalCase for classes/models

\- \*\*Frontend\*\*: snake\_case for XML layouts, PascalCase for Kotlin classes



\---



\## 🔐 Secrets \& Environment Variables



\### Backend (`.env.local` - NOT committed)

```env

DATABASE\_URL="mysql://user:pass@localhost:3306/ecotrace\_dev"

JWT\_SECRET="your-super-secret-key"

JWT\_EXPIRY="7d"

NODE\_ENV="development"

API\_URL="http://localhost:3000"

```



\### Frontend (Local properties - NOT committed)

```properties

\# gradle.properties

FIREBASE\_API\_KEY=xxx

API\_BASE\_URL=http://localhost:3000

```



\---



\## 📞 Communication



\- \*\*Sync Meetings\*\*: Every Monday \& Thursday at 10 AM

\- \*\*Chat\*\*: Slack/Discord channel

\- \*\*Issues\*\*: GitHub Issues for bugs/features

\- \*\*PRs\*\*: GitHub Pull Requests for code review



\---



\## 📚 Resources



\- \[Backend README](./backend/README.md)

\- \[Frontend README](./frontend/README.md)

\- \[API Documentation](./docs/API\_DOCUMENTATION.md)

\- \[Database Schema](./docs/DATABASE\_SCHEMA.md)

\- \[System Architecture](./docs/SYSTEM\_ARCHITECTURE.md)

\- \[Deployment Guide](./docs/DEPLOYMENT.md)



\---



\## 🤝 Contributing



See \[CONTRIBUTING.md](./CONTRIBUTING.md) for guidelines.



\---



\## 📄 License



Academic Project - University of Eastern Philippines



\---



\*\*Last Updated\*\*: June 2026

\*\*Status\*\*: Active Development 🚀



