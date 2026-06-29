# External System Integration Guide

## Overview

EcoTrace is designed to integrate with an **existing plant monitoring system** that maintains the primary plant database. Our system acts as a **verification and monitoring layer** on top of their data.

## Architecture

```
┌─────────────────────────────────────┐
│   External Plant Monitoring System  │
│   (Primary Data Source)             │
│                                     │
│   - Plant locations (GPS)           │
│   - Plant species                   │
│   - Planter information             │
│   - Initial planting dates          │
└──────────────┬──────────────────────┘
               │
               │ API Connection
               │ (To be established)
               ▼
┌─────────────────────────────────────┐
│   EcoTrace System                   │
│   (Verification & Monitoring)       │
│                                     │
│   - Student verifications           │
│   - Plant health tracking           │
│   - Growth measurements             │
│   - Photo documentation             │
└─────────────────────────────────────┘
```

## Data Flow

### What We Get FROM External System:
1. **Plant Basic Information**
   - Plant ID (their system's identifier)
   - GPS coordinates (latitude, longitude)
   - Plant species/type
   - Location address
   - Initial planting date
   - Who planted it (student/organization)

2. **Event Information** (if available)
   - Planting event details
   - Event organizers
   - Participating students/groups

### What We SEND TO External System:
1. **Verification Data**
   - Verification timestamp
   - Health status updates
   - Growth measurements (height, circumference)
   - Current plant condition
   - Photos (URLs or file transfers)

2. **Status Updates**
   - Plant survival status (alive/deceased)
   - Maintenance needs (water, fertilizer)
   - Issues detected (pests, disease)

---

## Integration Requirements

### API Endpoints We Need FROM Them

#### 1. Get Plant Details
```
GET /api/plants/{plant_id}

Response:
{
  "plant_id": "string",
  "latitude": "decimal",
  "longitude": "decimal",
  "species": "string",
  "planted_date": "date",
  "planted_by": "string",
  "location_address": "string"
}
```

#### 2. Get Plants by Location
```
GET /api/plants/nearby?lat={latitude}&lng={longitude}&radius={meters}

Response:
{
  "plants": [
    {
      "plant_id": "string",
      "latitude": "decimal",
      "longitude": "decimal",
      "species": "string",
      "distance_meters": "number"
    }
  ],
  "total": "number"
}
```

#### 3. Get Event Plants
```
GET /api/events/{event_id}/plants

Response:
{
  "event_id": "string",
  "event_name": "string",
  "plants": [
    {
      "plant_id": "string",
      "latitude": "decimal",
      "longitude": "decimal",
      "species": "string"
    }
  ]
}
```

#### 4. Get Planter Information
```
GET /api/planters/{planter_id}

Response:
{
  "planter_id": "string",
  "name": "string",
  "email": "string",
  "organization": "string"
}
```

---

### API Endpoints We PROVIDE TO Them

#### 1. Submit Verification
```
POST /api/external/verifications

Request:
{
  "external_plant_id": "string",
  "verified_by": "string",
  "health_status": "healthy|stressed|damaged|deceased",
  "plant_stage": "seed|seedling|sapling|young_tree|mature_tree",
  "height_cm": "decimal",
  "circumference_cm": "decimal",
  "notes": "string",
  "photos": ["url1", "url2"],
  "verified_at": "timestamp"
}

Response:
{
  "success": true,
  "verification_id": "string",
  "message": "Verification recorded"
}
```

#### 2. Get Plant Verification History
```
GET /api/external/plants/{external_plant_id}/verifications

Response:
{
  "plant_id": "string",
  "verifications": [
    {
      "verification_id": "string",
      "verified_by": "string",
      "health_status": "string",
      "height_cm": "decimal",
      "verified_at": "timestamp",
      "photos": ["url1", "url2"]
    }
  ],
  "total_verifications": "number"
}
```

#### 3. Get Plant Status Summary
```
GET /api/external/plants/{external_plant_id}/status

Response:
{
  "plant_id": "string",
  "current_status": "verified|pending|deceased",
  "last_verified_at": "timestamp",
  "verification_count": "number",
  "last_health_status": "string",
  "needs_attention": {
    "water": "boolean",
    "fertilizer": "boolean",
    "pests": "boolean",
    "disease": "boolean"
  }
}
```

---

## Database Mapping

### Their Schema → Our Schema

We need to map their data fields to our database:

| Their Field | Our Field | Table | Notes |
|-------------|-----------|-------|-------|
| plant_id | external_plant_id | ecotrace_plants | Store their ID for reference |
| latitude | latitude | ecotrace_plants | Direct mapping |
| longitude | longitude | ecotrace_plants | Direct mapping |
| species | plant_species | ecotrace_plants | Direct mapping |
| planted_date | planted_date | ecotrace_plants | Direct mapping |
| location | location_address | ecotrace_plants | Direct mapping |
| planter_id | created_by | ecotrace_plants | Map to our student_id if exists |
| event_id | event_id | ecotrace_event_tasks | Map to our event system |

### Schema Modifications Needed

Add external system reference to our plants table:

```sql
ALTER TABLE ecotrace_plants 
ADD COLUMN external_plant_id VARCHAR(255) UNIQUE AFTER plant_id,
ADD INDEX idx_external_plant_id (external_plant_id);
```

This allows us to:
- Store their plant ID for bidirectional reference
- Query plants by their system's ID
- Maintain data integrity across systems

---

## Synchronization Strategy

### Initial Data Import
1. **One-time bulk import** of existing plants from their system
2. **Create local records** in ecotrace_plants with external_plant_id reference
3. **Map planters** to our student accounts where possible

### Ongoing Synchronization

#### Option 1: Real-time (Webhook-based)
```
Their System              EcoTrace System
    │                          │
    │  New Plant Created       │
    ├──────────────────────────>│
    │  POST /webhook/plant     │
    │                          │
    │  Verification Added      │
    │<─────────────────────────┤
    │  POST /webhook/verify    │
```

#### Option 2: Periodic Sync (Polling)
```
EcoTrace System           Their System
    │                          │
    │  Every 5 minutes         │
    ├──────────────────────────>│
    │  GET /api/plants/updated │
    │                          │
    │  Get new/updated plants  │
    │<─────────────────────────┤
    │                          │
```

#### Option 3: Event-based (Recommended)
- They notify us when relevant changes occur
- We push verification updates in real-time
- Reduces API calls and improves data freshness

---

## Integration Implementation Plan

### Phase 1: Discovery & Planning
- [ ] Request their API documentation
- [ ] Review their database schema
- [ ] Identify authentication mechanism (API keys, OAuth, JWT)
- [ ] Determine rate limits and quotas
- [ ] Map data fields between systems
- [ ] Define error handling procedures

### Phase 2: Development
- [ ] Create external API client service (`lib/external_api.php`)
- [ ] Add external_plant_id column to database
- [ ] Build data import scripts
- [ ] Create API endpoint adapters
- [ ] Implement webhook receivers (if supported)
- [ ] Add error logging and monitoring

### Phase 3: Testing
- [ ] Test data retrieval from their system
- [ ] Test data push to their system
- [ ] Verify data consistency
- [ ] Load testing with bulk data
- [ ] Test error scenarios and recovery

### Phase 4: Deployment
- [ ] Initial bulk data import
- [ ] Enable real-time sync
- [ ] Monitor integration health
- [ ] Document any issues or limitations

---

## Error Handling

### Connection Issues
```php
// Retry logic with exponential backoff
function callExternalAPI($endpoint, $data, $maxRetries = 3) {
    $attempt = 0;
    while ($attempt < $maxRetries) {
        try {
            $response = makeAPICall($endpoint, $data);
            return $response;
        } catch (Exception $e) {
            $attempt++;
            if ($attempt >= $maxRetries) {
                logError("External API failed after {$maxRetries} attempts: " . $e->getMessage());
                throw $e;
            }
            sleep(pow(2, $attempt)); // Exponential backoff
        }
    }
}
```

### Data Inconsistencies
- **Missing plants**: Log and flag for manual review
- **Duplicate plants**: Use external_plant_id as unique identifier
- **Invalid data**: Validate before storing, reject if critical fields missing

### Fallback Strategy
If external system is unavailable:
1. Cache last known data
2. Allow EcoTrace to continue functioning
3. Queue verification updates for later sync
4. Display warning to users about potential staleness

---

## Security Considerations

### Authentication
- [ ] Store API credentials in environment variables (not in code)
- [ ] Use secure HTTPS connections only
- [ ] Implement API key rotation policy
- [ ] Set up separate staging and production credentials

### Data Privacy
- [ ] Only request minimum necessary data
- [ ] Don't store sensitive personal information
- [ ] Comply with data retention policies
- [ ] Implement data encryption for transfers

### Access Control
- [ ] Limit which endpoints can access external API
- [ ] Log all external API calls for audit trail
- [ ] Rate limit our outgoing requests
- [ ] Implement circuit breaker pattern

---

## Configuration Template

Create `backend/config/external_api.php`:

```php
<?php
/**
 * External Plant System API Configuration
 * DO NOT commit actual credentials to version control
 */

// API Endpoint
define('EXTERNAL_API_BASE_URL', getenv('EXTERNAL_API_URL') ?: 'https://api.external-system.com/v1');

// Authentication
define('EXTERNAL_API_KEY', getenv('EXTERNAL_API_KEY') ?: '');
define('EXTERNAL_API_SECRET', getenv('EXTERNAL_API_SECRET') ?: '');

// Connection Settings
define('EXTERNAL_API_TIMEOUT', 30); // seconds
define('EXTERNAL_API_MAX_RETRIES', 3);

// Sync Settings
define('EXTERNAL_SYNC_ENABLED', getenv('EXTERNAL_SYNC_ENABLED') === 'true');
define('EXTERNAL_SYNC_INTERVAL', 300); // seconds (5 minutes)

// Webhook Settings (if they support it)
define('EXTERNAL_WEBHOOK_SECRET', getenv('EXTERNAL_WEBHOOK_SECRET') ?: '');
define('EXTERNAL_WEBHOOK_ENDPOINT', '/api/webhooks/external');

// Feature Flags
define('EXTERNAL_ALLOW_PLANT_IMPORT', true);
define('EXTERNAL_ALLOW_VERIFICATION_PUSH', true);
define('EXTERNAL_ALLOW_STATUS_UPDATES', true);
```

---

## Testing Checklist

### Before Going Live
- [ ] Verify all API endpoints work as expected
- [ ] Test with production-like data volumes
- [ ] Confirm data mapping is correct
- [ ] Test authentication and authorization
- [ ] Verify error handling and logging
- [ ] Test sync process (initial + incremental)
- [ ] Confirm webhook delivery (if applicable)
- [ ] Test fallback behavior when external system is down
- [ ] Review security measures
- [ ] Document any limitations or known issues

---

## Questions to Ask External System Team

### Technical Questions
1. What is your API base URL and version?
2. What authentication method do you use? (API key, OAuth, JWT)
3. What are the rate limits for API calls?
4. Do you support webhooks for real-time updates?
5. What is your API uptime SLA?
6. How do you handle pagination for large datasets?
7. What date/time format do you use?
8. How are coordinates formatted? (decimal degrees, DMS, etc.)

### Data Questions
1. What is the unique identifier for plants in your system?
2. Can we get a sample API response?
3. What fields are guaranteed to be present vs optional?
4. How do you represent plant species? (common names, scientific names, codes)
5. Do you track who planted each tree?
6. Do you maintain event/campaign information?
7. How often is data updated in your system?

### Integration Questions
1. Is there a test/sandbox environment we can use?
2. Can you provide sample API credentials for testing?
3. What's the process for reporting bugs or issues?
4. Who is the technical contact for API questions?
5. Are there any costs associated with API usage?
6. What's the process for requesting new endpoints or features?

---

## Future Enhancements

Once we have their schema and API:

1. **Two-way sync dashboard**
   - Visual status of sync health
   - Failed sync attempts
   - Data discrepancy reports

2. **Conflict resolution**
   - Handle when same plant is modified in both systems
   - Version control or "last write wins" strategy

3. **Bulk operations**
   - Batch import/export tools
   - Data migration utilities

4. **Analytics integration**
   - Cross-system reporting
   - Combined statistics dashboard

5. **Automated reconciliation**
   - Scheduled data consistency checks
   - Automatic correction of minor discrepancies

---

## Contact Information

**When Ready to Integrate:**
1. Request API documentation from external system team
2. Schedule technical meeting to discuss integration
3. Update this document with actual schema and endpoints
4. Begin Phase 1 of implementation plan

**Document Last Updated:** 2026-06-29
**Status:** Awaiting external system API documentation
**Next Action:** Contact external system team for API access