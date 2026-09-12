<?php

/**
 * Normalize text before processing.
 */
function recommendationNormalizeText($text)
{
    $text = html_entity_decode(
        (string) $text,
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );

    $text = strip_tags($text);

    $text = strtolower($text);

    // Keep letters, numbers and useful technical characters.
    $text = preg_replace('/[^a-z0-9+#.\- ]+/i', ' ', $text);

    $text = preg_replace('/\s+/', ' ', $text);

    return trim($text);
}


/**
 * Convert text into unique normalized words.
 */
function recommendationTokens($text)
{
    $text = recommendationNormalizeText($text);

    if ($text === '') {
        return [];
    }

    $words = preg_split('/[\s,;|\/]+/', $text);

    $stopWords = [
        'and',
        'or',
        'the',
        'a',
        'an',
        'of',
        'to',
        'in',
        'for',
        'with',
        'on',
        'at',
        'is',
        'are',
        'be',
        'from',
        'by',
        'as',
        'this',
        'that',
        'we',
        'you',
        'your',
        'our',
        'will',
        'can',
        'who',
        'has',
        'have',
        'their',
        'they',
        'job',
        'work',
        'working'
    ];

    $tokens = [];

    foreach ($words as $word) {

        $word = trim($word);

        if ($word === '') {
            continue;
        }

        if (strlen($word) < 2) {
            continue;
        }

        if (in_array($word, $stopWords, true)) {
            continue;
        }

        $tokens[$word] = true;
    }

    return array_keys($tokens);
}


/**
 * Calculate token similarity.
 */
function recommendationTokenSimilarity($userText, $jobText)
{
    $userTokens = recommendationTokens($userText);
    $jobTokens = recommendationTokens($jobText);

    if (
        count($userTokens) === 0 ||
        count($jobTokens) === 0
    ) {
        return 0;
    }

    $jobTokenMap = array_fill_keys($jobTokens, true);

    $matched = 0;

    foreach ($userTokens as $token) {

        if (isset($jobTokenMap[$token])) {
            $matched++;
        }
    }

    return ($matched / count($userTokens)) * 100;
}


/**
 * Calculate skill similarity.
 */
function recommendationSkillSimilarity($userSkills, $jobText)
{
    $skills = recommendationTokens($userSkills);
    $jobTokens = recommendationTokens($jobText);

    if (
        count($skills) === 0 ||
        count($jobTokens) === 0
    ) {
        return 0;
    }

    $jobTokenMap = array_fill_keys($jobTokens, true);

    $matched = 0;

    foreach ($skills as $skill) {

        if (isset($jobTokenMap[$skill])) {
            $matched++;
        }
    }

    return ($matched / count($skills)) * 100;
}


/**
 * Calculate job title similarity.
 */
function recommendationTitleSimilarity($userTitle, $jobTitle)
{
    $userTokens = recommendationTokens($userTitle);
    $jobTokens = recommendationTokens($jobTitle);

    if (
        count($userTokens) === 0 ||
        count($jobTokens) === 0
    ) {
        return 0;
    }

    $jobTokenMap = array_fill_keys($jobTokens, true);

    $matched = 0;

    foreach ($userTokens as $token) {

        if (isset($jobTokenMap[$token])) {
            $matched++;
        }
    }

    $score = ($matched / count($userTokens)) * 100;

    return min(100, $score);
}


/**
 * Calculate education similarity.
 */
function recommendationEducationSimilarity($userEducation, $jobEducation)
{
    return recommendationTokenSimilarity(
        $userEducation,
        $jobEducation
    );
}


/**
 * Calculate region similarity.
 *
 * users.region_id -> job_regions.id
 * jobs.job_region contains the region text/code.
 */
function recommendationRegionSimilarity(
    $userRegionName,
    $userRegionCode,
    $jobRegion
) {
    $userRegionName = recommendationNormalizeText($userRegionName);
    $userRegionCode = recommendationNormalizeText($userRegionCode);
    $jobRegion = recommendationNormalizeText($jobRegion);

    if ($jobRegion === '') {
        return 0;
    }

    /*
     * Exact region name match.
     */
    if (
        $userRegionName !== '' &&
        $userRegionName === $jobRegion
    ) {
        return 100;
    }

    /*
     * Exact region code match.
     */
    if (
        $userRegionCode !== '' &&
        $userRegionCode === $jobRegion
    ) {
        return 100;
    }

    /*
     * Token matching for region.
     */
    $userRegionTokens = recommendationTokens(
        $userRegionName . ' ' . $userRegionCode
    );

    $jobRegionTokens = recommendationTokens($jobRegion);

    if (
        count($userRegionTokens) === 0 ||
        count($jobRegionTokens) === 0
    ) {
        return 0;
    }

    $jobRegionMap = array_fill_keys(
        $jobRegionTokens,
        true
    );

    $matched = 0;

    foreach ($userRegionTokens as $token) {

        if (isset($jobRegionMap[$token])) {
            $matched++;
        }
    }

    if ($matched === 0) {
        return 0;
    }

    return ($matched / count($userRegionTokens)) * 100;
}


/**
 * Get recommended jobs for a job seeker.
 */
function getRecommendedJobs(PDO $conn, $userId, $limit = 10)
{
    $limit = max(
        1,
        min((int) $limit, 50)
    );


    /*
     * ---------------------------------------------------------
     * GET USER PROFILE
     * ---------------------------------------------------------
     *
     * Based exactly on the SQL structure:
     *
     * users.region_id -> job_regions.id
     */
    $userQuery = $conn->prepare("
        SELECT
            u.id,
            u.skills,
            u.education,
            u.title,
            u.bio,
            u.address,
            u.region_id,

            r.name AS region_name,
            r.code AS region_code

        FROM users u

        LEFT JOIN job_regions r
            ON r.id = u.region_id

        WHERE u.id = :user_id
          AND u.type = 'Job Seeker'

        LIMIT 1
    ");

    $userQuery->execute([
        ':user_id' => $userId
    ]);

    $user = $userQuery->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        return [];
    }


    /*
     * ---------------------------------------------------------
     * GET JOBS ALREADY APPLIED FOR
     * ---------------------------------------------------------
     */
    $appliedQuery = $conn->prepare("
        SELECT job_id

        FROM job_applications

        WHERE worker_id = :worker_id
    ");

    $appliedQuery->execute([
        ':worker_id' => $userId
    ]);

    $appliedJobIds = $appliedQuery->fetchAll(
        PDO::FETCH_COLUMN
    );

    $appliedJobIds = array_map(
        'intval',
        $appliedJobIds
    );


    /*
     * ---------------------------------------------------------
     * GET ACTIVE JOBS
     * ---------------------------------------------------------
     */
    $jobsQuery = $conn->query("
        SELECT
            id,
            job_title,
            job_region,
            job_type,
            work_arrangement,
            vacancy,
            job_category,
            experience,
            salary,
            application_deadline,
            job_description,
            responsibilities,
            education_experience,
            company_name,
            company_id,
            company_image,
            status,
            created_at,
            view_count

        FROM jobs

        WHERE status = 1

        ORDER BY created_at DESC
    ");

    $jobs = $jobsQuery->fetchAll(
        PDO::FETCH_ASSOC
    );


    /*
     * ---------------------------------------------------------
     * BUILD RECOMMENDATIONS
     * ---------------------------------------------------------
     */
    $recommendations = [];

    foreach ($jobs as $job) {

        $jobId = (int) $job['id'];


        /*
         * Don't recommend jobs already applied for.
         */
        if (
            in_array(
                $jobId,
                $appliedJobIds,
                true
            )
        ) {
            continue;
        }


        /*
         * Combine searchable job information.
         */
        $jobText = implode(' ', [

            $job['job_title'] ?? '',

            $job['job_region'] ?? '',

            $job['job_type'] ?? '',

            $job['work_arrangement'] ?? '',

            $job['job_category'] ?? '',

            $job['experience'] ?? '',

            $job['job_description'] ?? '',

            $job['responsibilities'] ?? '',

            $job['education_experience'] ?? ''

        ]);


        /*
         * -----------------------------------------------------
         * SKILLS SCORE
         * -----------------------------------------------------
         */
        $skillScore = recommendationSkillSimilarity(
            $user['skills'] ?? '',
            $jobText
        );


        /*
         * -----------------------------------------------------
         * TITLE SCORE
         * -----------------------------------------------------
         */
        $titleScore = recommendationTitleSimilarity(
            $user['title'] ?? '',
            $job['job_title'] ?? ''
        );


        /*
         * -----------------------------------------------------
         * EDUCATION SCORE
         * -----------------------------------------------------
         */
        $educationScore = recommendationEducationSimilarity(
            $user['education'] ?? '',
            $job['education_experience'] ?? ''
        );


        /*
         * -----------------------------------------------------
         * REGION SCORE
         * -----------------------------------------------------
         */
        $regionScore = recommendationRegionSimilarity(

            $user['region_name'] ?? '',

            $user['region_code'] ?? '',

            $job['job_region'] ?? ''

        );


        /*
         * -----------------------------------------------------
         * FINAL WEIGHTED SCORE
         * -----------------------------------------------------
         *
         * Skills       = 50%
         * Job Title    = 20%
         * Education    = 15%
         * Region       = 15%
         *
         * Total        = 100%
         */
        $finalScore =

            ($skillScore * 0.50) +

            ($titleScore * 0.20) +

            ($educationScore * 0.15) +

            ($regionScore * 0.15);


        /*
         * Save scores.
         */
        $job['recommendation_score'] =
            round($finalScore, 2);

        $job['skill_score'] =
            round($skillScore, 2);

        $job['title_score'] =
            round($titleScore, 2);

        $job['education_score'] =
            round($educationScore, 2);

        $job['region_score'] =
            round($regionScore, 2);


        $recommendations[] = $job;
    }


    /*
     * ---------------------------------------------------------
     * SORT BY BEST MATCH
     * ---------------------------------------------------------
     */
    usort(
        $recommendations,
        function ($first, $second) {

            if (
                $first['recommendation_score'] ==
                $second['recommendation_score']
            ) {

                return strtotime(
                    $second['created_at']
                ) <=> strtotime(
                    $first['created_at']
                );
            }

            return
                $second['recommendation_score']
                <=>
                $first['recommendation_score'];
        }
    );


    /*
     * Return requested number of jobs.
     */
    return array_slice(
        $recommendations,
        0,
        $limit
    );
}