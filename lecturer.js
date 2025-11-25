const express = require("express");
const router = express.Router();
const bcrypt = require("bcryptjs");
const db = require("../db");
const { checkAndSetPublishedDate } = require("./student");

// Login
router.post("/login_lecturer", async (req, res) => {
  try {
    let { username, password } = req.body || {};
    if (!username || !password) {
      return res.status(400).json({
        success: false,
        message: "Username and password are required.",
      });
    }

    username = String(username).trim();
    password = String(password).trim();

    const [rows] = await db.query(
      "SELECT * FROM lecturer WHERE LOWER(nim_nik_unit) = LOWER(?) OR LOWER(email_polibatam) = LOWER(?)",
      [username, username]
    );

    if (!rows || rows.length === 0) {
      return res
        .status(401)
        .json({ success: false, message: "Account not found." });
    }

    const user = rows[0];

    if (user.status && user.status.toLowerCase() !== "active") {
      return res
        .status(403)
        .json({ success: false, message: "Account inactive." });
    }

    const stored = (user.password || "").trim();
    let passwordMatch = false;

    if (
      stored.startsWith("$2a$") ||
      stored.startsWith("$2b$") ||
      stored.startsWith("$2y$")
    ) {
      passwordMatch = await bcrypt.compare(password, stored);
    } else {
      passwordMatch = stored === password;
    }

    if (!passwordMatch) {
      return res
        .status(401)
        .json({ success: false, message: "Wrong password." });
    }

    return res.status(200).json({
      success: true,
      message: "Login successfull.",
      user: {
        nim_nik_unit: user.nim_nik_unit,
        name: user.name,
        email_polibatam: user.email_polibatam,
        role: "lecturer",
        is_koor: user.is_koor || 0,
        prodi_koor: user.prodi_koor || null,
        status: user.status,
        id_kampus: user.id_kampus || null,
      },
    });
  } catch (err) {
    console.error("[LOGIN LECTURER] error:", err);
    return res
      .status(500)
      .json({ success: false, message: "An error occurred on the server." });
  }
});

// List submission mahasiswa
router.get("/lecturer/submissions/:nim_nik_unit", async (req, res) => {
  const { nim_nik_unit } = req.params;

  try {
    const [lecRows] = await db.query(
      `SELECT prodi_koor, id_kampus 
       FROM lecturer 
       WHERE nim_nik_unit = ? AND is_koor = 1 
       LIMIT 1`,
      [nim_nik_unit]
    );

    if (!lecRows.length) {
      return res.status(403).json({
        success: false,
        message: "You are not the internship coordinator.",
      });
    }

    const { prodi_koor, id_kampus } = lecRows[0];

    const [rows] = await db.query(
      `SELECT 
         il.id_letter,
         il.nim,
         s.name AS student_name,
         il.company_name,
         il.start_date,
         il.end_date,
         il.status,
         il.koor_approval,
         il.cdc_approval,
         il.company_reply_letter,
         il.acceptance_status,
         il.created_at,
         il.updated_at
       FROM internship_letter il
       JOIN student_internship s ON s.nim = il.nim
       WHERE s.program_study LIKE CONCAT('%', ?, '%') 
         AND s.id_kampus = ?
       ORDER BY il.created_at DESC`,
      [prodi_koor, id_kampus]
    );

    res.json({ success: true, data: rows });
  } catch (err) {
    console.error("[KOOR] Error fetching submissions:", err);
    res.status(500).json({ success: false, message: "Server error" });
  }
});

// Detail submission
router.get("/lecturer/submissions/detail/:id_letter", async (req, res) => {
  const { id_letter } = req.params;

  try {
    const [rows] = await db.query(
      `SELECT 
         il.id_letter,
         il.nim,
         s.name AS student_name,
         s.program_study,
         il.company_name,
         il.company_address,
         il.company_contact,
         il.start_date,
         il.end_date,
         il.status,
         il.koor_approval,
         il.cdc_approval,
         il.company_reply_letter,
         il.acceptance_status,
         il.created_at,
         il.updated_at
       FROM internship_letter il
       JOIN student_internship s ON s.nim = il.nim
       WHERE il.id_letter = ?`,
      [id_letter]
    );

    if (!rows.length) {
      return res
        .status(404)
        .json({ success: false, message: "Submission not found" });
    }

    res.json({ success: true, data: rows[0] });
  } catch (err) {
    console.error("[KOOR] Error fetching submission detail:", err);
    res.status(500).json({ success: false, message: "Server error" });
  }
});

// Approve/Reject submission (Lecturer / Coordinator)
router.post("/lecturer/approval", async (req, res) => {
  try {
    const { id_letter, status, user_id, user_name, comment } = req.body;

    if (!id_letter || !status) {
      return res.status(400).json({
        success: false,
        message: "id_letter and status are required.",
      });
    }

    const s = status.toUpperCase();
    if (!["ACCEPTED", "REJECTED"].includes(s)) {
      return res
        .status(400)
        .json({ success: false, message: "Status invalid." });
    }

    // fetch current letter
    const [rows] = await db.query(
      `SELECT id_letter, koor_approval
       FROM internship_letter
       WHERE id_letter = ? LIMIT 1`,
      [id_letter]
    );

    if (!rows.length) {
      return res
        .status(404)
        .json({ success: false, message: "Submission not found." });
    }

    const row = rows[0];

    // For lecturer action, ensure current koor_approval is WAITING (so coordinator can act)
    if ((row.koor_approval || "").toUpperCase() !== "WAITING") {
      return res.status(400).json({
        success: false,
        message: "The submission is not awaiting Coordinator approval.",
      });
    }

    // Apply update to internship_letter (koor's action)
    if (s === "ACCEPTED") {
      await db.query(
        `UPDATE internship_letter
         SET koor_approval = 'ACCEPTED',
             status = 'WAITING',
             cdc_approval = 'WAITING',
             updated_at = NOW()
         WHERE id_letter = ?`,
        [id_letter]
      );
    } else {
      await db.query(
        `UPDATE internship_letter
         SET koor_approval = 'REJECTED',
             status = 'REJECTED',
             cdc_approval = 'REJECTED',
             updated_at = NOW()
         WHERE id_letter = ?`,
        [id_letter]
      );
    }

    // --- Insert into internship_letter_history ---
    let approved_by = "INTERNSHIP COORDINATOR";
    let user_id_val = "-";
    let user_name_val = "-";

    try {
      // try server-side lookup from session if present
      const actor = req.user || req.session?.user || null;
      if (actor) {
        // If you keep lecturer session with nim_nik_unit stored, try to look it up
        if (
          (actor.role && actor.role.toLowerCase().includes("lecturer")) ||
          actor.is_koor
        ) {
          const [lrows] = await db.query(
            `SELECT nim_nik_unit, name FROM lecturer WHERE nim_nik_unit = ? LIMIT 1`,
            [actor.nim_nik_unit || actor.id]
          );
          if (lrows && lrows.length) {
            user_id_val = lrows[0].nim_nik_unit || "-";
            user_name_val = lrows[0].name || "-";
            approved_by = "INTERNSHIP COORDINATOR";
          }
        } else {
          user_id_val = actor.id || "-";
          user_name_val = actor.name || "-";
        }
      }
    } catch (err) {
      console.error("[KOOR] user lookup error:", err);
    }

    // fallback to values sent by client if server-side lookup missing
    const final_user_id =
      user_id_val && user_id_val !== "-" ? user_id_val : user_id || "-";
    const final_user_name =
      user_name_val && user_name_val !== "-" ? user_name_val : user_name || "-";

    await db.query(
      `INSERT INTO internship_letter_history 
       (id_letter, approved_by, user_id, user_name, status_approval, timestamp, comment)
       VALUES (?, ?, ?, ?, ?, NOW(), ?)`,
      [
        id_letter,
        approved_by,
        final_user_id,
        final_user_name,
        s,
        comment || null,
      ]
    );

    // Cek dan set published_date jika kedua approval sudah ACCEPTED
    await checkAndSetPublishedDate(id_letter);

    return res.json({
      success: true,
      message: `Submission has been ${s.toLowerCase()} by Internship Coordinator.`,
    });
  } catch (err) {
    console.error("[LECTURER] approval error:", err);
    return res.status(500).json({
      success: false,
      message: "Server error: " + err.message,
    });
  }
});

// get latest rejected reason by CDC for a letter
router.get("/lecturer/reason/:id", async (req, res) => {
  try {
    const id = req.params.id;
    const [rows] = await db.query(
      `SELECT comment, timestamp, approved_by, user_name, user_id
       FROM internship_letter_history
       WHERE id_letter = ? AND status_approval = 'REJECTED' AND approved_by = 'INTERNSHIP COORDINATOR'
       ORDER BY timestamp DESC LIMIT 1`,
      [id]
    );
    if (!rows.length)
      return res
        .status(404)
        .json({ success: false, message: "Reason not found." });
    res.json({
      success: true,
      comment: rows[0].comment,
      meta: { user_name: rows[0].user_name, timestamp: rows[0].timestamp },
    });
  } catch (err) {
    console.error("[CDC] reason fetch error:", err);
    res.status(500).json({ success: false, message: "Server error" });
  }
});

// edit the latest CDC rejection reason for a letter
router.post("/lecturer/history/:id/edit", async (req, res) => {
  try {
    const id = req.params.id;
    const { comment } = req.body;
    if (!comment || !comment.trim())
      return res
        .status(400)
        .json({ success: false, message: "Comment is required." });

    // Find the latest history row to update
    const [rows] = await db.query(
      `SELECT id_history FROM internship_letter_history
       WHERE id_letter = ? AND status_approval = 'REJECTED' AND approved_by = 'INTERNSHIP COORDINATOR'
       ORDER BY timestamp DESC LIMIT 1`,
      [id]
    );
    if (!rows.length)
      return res
        .status(404)
        .json({ success: false, message: "History record not found." });

    const id_history = rows[0].id_history;
    await db.query(
      `UPDATE internship_letter_history
       SET comment = ?, timestamp = NOW()
       WHERE id_history = ?`,
      [comment, id_history]
    );

    res.json({ success: true, message: "Reason updated." });
  } catch (err) {
    console.error("[CDC] history edit error:", err);
    res.status(500).json({ success: false, message: "Server error" });
  }
});

// Get company reply file info
router.get("/lecturer/company-reply/:id_letter", async (req, res) => {
  try {
    const { id_letter } = req.params;

    const [rows] = await db.query(
      `SELECT 
         il.id_letter,
         il.acceptance_status,
         il.company_reply_letter,
         il.published_date,
         il.company_name,
         il.updated_at,
         il.nim,
         s.name AS student_name
       FROM internship_letter il
       JOIN student_internship s ON s.nim = il.nim
       WHERE il.id_letter = ?`,
      [id_letter]
    );

    if (!rows.length) {
      return res.status(404).json({
        success: false,
        message: "Submission not found",
      });
    }

    const data = rows[0];

    // Hitung apakah sudah lebih dari 14 hari
    let isOverdue = false;
    let reason = null;

    if (data.published_date) {
      const publishedDate = new Date(data.published_date);
      const currentDate = new Date();
      const diffTime = currentDate - publishedDate;
      const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

      if (diffDays > 14 && !data.company_reply_letter) {
        isOverdue = true;
        reason =
          "Students have not received a response to their internship application within 14 days after completing the internship interview.";
      }
    }

    res.json({
      success: true,
      data: {
        ...data,
        isOverdue,
        reason,
      },
    });
  } catch (err) {
    console.error("[LECTURER] Error fetching company reply:", err);
    res.status(500).json({
      success: false,
      message: "Server error",
    });
  }
});

/**
 * GET /api/student/dashboard/statistics
 */
router.get("/dashboard/statistics", async (req, res) => {
  try {
    const { department, year } = req.query;
    const currentYear = year || new Date().getFullYear();
    const id_kampus = 1;

    const allProgramsQuery = `
          SELECT 
            CONCAT(ps.jenjang, ' ', ps.study_program) AS program_full_name,
            ps.major AS department,
            ps.kode_prodi
          FROM program_study ps
          WHERE ps.id_kampus = ?
            ${department ? "AND ps.major = ?" : ""}
          ORDER BY ps.major, program_full_name
        `;

    const allProgramsParams = department
      ? [id_kampus, department]
      : [id_kampus];

    const [allPrograms] = await db.query(allProgramsQuery, allProgramsParams);

    // STEP 2: RESPONSE TIME (Koor + CDC)
    const responseTimeQuery = `
          SELECT 
            CONCAT(ps.jenjang, ' ', ps.study_program) AS program_full_name,
            ps.major AS department,
            AVG(DATEDIFF(ilh_koor.timestamp, il.created_at)) AS avg_response_time_koor,
            AVG(DATEDIFF(ilh_cdc.timestamp, ilh_koor.timestamp)) AS avg_response_time_cdc,
            AVG(DATEDIFF(ilh_cdc.timestamp, il.created_at)) AS avg_total_response_time,
            COUNT(*) AS data_count
          FROM internship_letter il
          INNER JOIN student_internship si ON il.nim = si.nim
          INNER JOIN program_study ps 
            ON si.program_study = ps.kode_prodi 
           AND si.id_kampus = ps.id_kampus
          LEFT JOIN (
            SELECT id_letter, MIN(timestamp) AS timestamp
            FROM internship_letter_history
            WHERE LOWER(approved_by) = 'internship coordinator'
              AND LOWER(status_approval) = 'accepted'
            GROUP BY id_letter
          ) ilh_koor ON il.id_letter = ilh_koor.id_letter
          LEFT JOIN (
            SELECT id_letter, MIN(timestamp) AS timestamp
            FROM internship_letter_history
            WHERE LOWER(approved_by) = 'cdc administrator'
              AND LOWER(status_approval) = 'accepted'
            GROUP BY id_letter
          ) ilh_cdc ON il.id_letter = ilh_cdc.id_letter
          WHERE YEAR(il.created_at) = ?
            AND ps.id_kampus = ?
            ${department ? "AND ps.major = ?" : ""}
            AND ilh_koor.timestamp IS NOT NULL
            AND ilh_cdc.timestamp IS NOT NULL
          GROUP BY program_full_name, ps.major
        `;

    const responseTimeParams = department
      ? [currentYear, id_kampus, department]
      : [currentYear, id_kampus];

    const [responseTimeData] = await db.query(
      responseTimeQuery,
      responseTimeParams
    );

    // STEP 3: ACCEPTANCE RATE (Company Response)
    const acceptanceRateQuery = `
          SELECT 
            CONCAT(ps.jenjang, ' ', ps.study_program) AS program_full_name,
            ps.major AS department,
            COUNT(CASE WHEN il.acceptance_status = 'ACCEPTED' THEN 1 END) AS accepted_count,
            COUNT(CASE WHEN il.acceptance_status = 'REJECTED' THEN 1 END) AS rejected_count,
            COUNT(*) AS total_count,
            ROUND((COUNT(CASE WHEN il.acceptance_status = 'ACCEPTED' THEN 1 END) / COUNT(*)) * 100, 2) AS acceptance_rate,
            ROUND((COUNT(CASE WHEN il.acceptance_status = 'REJECTED' THEN 1 END) / COUNT(*)) * 100, 2) AS rejection_rate
          FROM internship_letter il
          INNER JOIN student_internship si ON il.nim = si.nim
          INNER JOIN program_study ps 
            ON si.program_study = ps.kode_prodi 
           AND si.id_kampus = ps.id_kampus
          WHERE YEAR(il.created_at) = ?
            AND ps.id_kampus = ?
            ${department ? "AND ps.major = ?" : ""}
            AND il.acceptance_status IN ('ACCEPTED', 'REJECTED')
          GROUP BY program_full_name, ps.major
        `;

    const acceptanceRateParams = department
      ? [currentYear, id_kampus, department]
      : [currentYear, id_kampus];

    const [acceptanceRateData] = await db.query(
      acceptanceRateQuery,
      acceptanceRateParams
    );

    // STEP 4: MERGE DATA - ALL PROGRAMS WITH ACTUAL DATA
    const responseTimeMap = new Map();
    responseTimeData.forEach((r) => {
      const key = `${r.department}|||${r.program_full_name}`;
      responseTimeMap.set(key, {
        avgResponseTimeKoor: Number(r.avg_response_time_koor || 0).toFixed(2),
        avgResponseTimeCdc: Number(r.avg_response_time_cdc || 0).toFixed(2),
        avgTotalResponseTime: Number(r.avg_total_response_time || 0).toFixed(2),
        dataCount: r.data_count || 0,
      });
    });

    const acceptanceRateMap = new Map();
    acceptanceRateData.forEach((r) => {
      const key = `${r.department}|||${r.program_full_name}`;
      acceptanceRateMap.set(key, {
        acceptedCount: r.accepted_count || 0,
        rejectedCount: r.rejected_count || 0,
        totalCount: r.total_count || 0,
        acceptanceRate: Number(r.acceptance_rate || 0),
        rejectionRate: Number(r.rejection_rate || 0),
      });
    });

    const responseTimeResult = [];
    const acceptanceRateResult = [];

    allPrograms.forEach((program) => {
      const key = `${program.department}|||${program.program_full_name}`;

      // Response Time Data
      const responseData = responseTimeMap.get(key);
      responseTimeResult.push({
        program: program.program_full_name,
        department: program.department,
        avgResponseTimeKoor: responseData
          ? responseData.avgResponseTimeKoor
          : "0.00",
        avgResponseTimeCdc: responseData
          ? responseData.avgResponseTimeCdc
          : "0.00",
        avgTotalResponseTime: responseData
          ? responseData.avgTotalResponseTime
          : "0.00",
        hasData: !!responseData,
        dataCount: responseData ? responseData.dataCount : 0,
      });

      // Acceptance Rate Data
      const acceptanceData = acceptanceRateMap.get(key);
      acceptanceRateResult.push({
        program: program.program_full_name,
        department: program.department,
        acceptedCount: acceptanceData ? acceptanceData.acceptedCount : 0,
        rejectedCount: acceptanceData ? acceptanceData.rejectedCount : 0,
        totalCount: acceptanceData ? acceptanceData.totalCount : 0,
        acceptanceRate: acceptanceData ? acceptanceData.acceptanceRate : 0,
        rejectionRate: acceptanceData ? acceptanceData.rejectionRate : 0,
        hasData: !!acceptanceData,
      });
    });

    // STEP 5: GET UNIQUE DEPARTMENTS LIST
    const [departments] = await db.query(
      `
          SELECT DISTINCT major AS department
          FROM program_study
          WHERE id_kampus = ?
          ORDER BY major
        `,
      [id_kampus]
    );

    // FINAL RESPONSE
    res.json({
      success: true,
      data: {
        year: currentYear,
        department: department || "All Departments",
        departments: departments.map((d) => d.department),
        responseTime: responseTimeResult,
        acceptanceRate: acceptanceRateResult,
      },
    });
  } catch (err) {
    console.error("Dashboard statistics error:", err);
    res.status(500).json({
      success: false,
      message: "Server error",
      error: err.message,
    });
  }
});

// DASHBOARD SUMMARY
/**
 * GET /api/student/dashboard/summary
 */
router.get("/dashboard/summary", async (req, res) => {
  try {
    const { year } = req.query;
    const currentYear = year || new Date().getFullYear();
    const id_kampus = 1; // Polibatam

    // TOTAL SUBMISSIONS
    const [totalSubmissions] = await db.query(
      `SELECT COUNT(*) AS total
       FROM internship_letter il
       INNER JOIN student_internship si ON il.nim = si.nim
       WHERE YEAR(il.created_at) = ?
         AND si.id_kampus = ?`,
      [currentYear, id_kampus]
    );

    // STATUS BREAKDOWN
    const [statusBreakdown] = await db.query(
      `SELECT il.status, COUNT(*) AS count
       FROM internship_letter il
       INNER JOIN student_internship si ON il.nim = si.nim
       WHERE YEAR(il.created_at) = ?
         AND si.id_kampus = ?
       GROUP BY il.status`,
      [currentYear, id_kampus]
    );

    // AVERAGE RESPONSE TIME
    const [avgResponseTime] = await db.query(
      `
      SELECT AVG(DATEDIFF(ilh_cdc.timestamp, il.created_at)) AS avg_days
      FROM internship_letter il
      INNER JOIN student_internship si ON il.nim = si.nim
      LEFT JOIN (
        SELECT id_letter, MIN(timestamp) AS timestamp
        FROM internship_letter_history
        WHERE LOWER(approved_by) = 'internship coordinator'
          AND LOWER(status_approval) = 'accepted'
        GROUP BY id_letter
      ) ilh_koor ON il.id_letter = ilh_koor.id_letter
      LEFT JOIN (
        SELECT id_letter, MIN(timestamp) AS timestamp
        FROM internship_letter_history
        WHERE LOWER(approved_by) = 'cdc administrator'
          AND LOWER(status_approval) = 'accepted'
        GROUP BY id_letter
      ) ilh_cdc ON il.id_letter = ilh_cdc.id_letter
      WHERE YEAR(il.created_at) = ?
        AND si.id_kampus = ?
        AND ilh_koor.timestamp IS NOT NULL
        AND ilh_cdc.timestamp IS NOT NULL
    `,
      [currentYear, id_kampus]
    );

    const [companyAcceptance] = await db.query(
      `SELECT il.acceptance_status, COUNT(*) AS count
       FROM internship_letter il
       INNER JOIN student_internship si ON il.nim = si.nim
       WHERE YEAR(il.created_at) = ?
         AND si.id_kampus = ?
         AND il.status = 'ACCEPTED'
       GROUP BY il.acceptance_status`,
      [currentYear, id_kampus]
    );

    // FINAL RESPONSE
    res.json({
      success: true,
      data: {
        year: currentYear,
        totalSubmissions: totalSubmissions[0].total,
        statusBreakdown,
        averageResponseTime: Number(avgResponseTime[0].avg_days || 0).toFixed(
          2
        ),
        companyAcceptance,
      },
    });
  } catch (err) {
    console.error("Dashboard summary error:", err);
    res.status(500).json({
      success: false,
      message: "Server error",
      error: err.message,
    });
  }
});

// ============================================================
// EXPORT DATA MAGANG - COORDINATOR SPECIFIC WITH FALLBACK
// ============================================================

/**
 * GET /api/lecturer/export-internship
 * Export data magang untuk coordinator tertentu
 * Query params: year, nim_nik_unit
 *
 * LOGIC:
 * 1. Ambil data dari tabel internship (mahasiswa aktif magang)
 * 2. Jika kolom kosong di internship, fallback ke internship_letter
 */
router.get("/lecturer/export-internship", async (req, res) => {
  try {
    const { year, nim_nik_unit } = req.query;
    const currentYear = year || new Date().getFullYear();

    // VALIDASI: Cek apakah nim_nik_unit dikirim
    if (!nim_nik_unit) {
      return res.status(400).json({
        success: false,
        message: "Lecturer ID (nim_nik_unit) is required",
      });
    }

    // STEP 1: Get coordinator info
    const [lecRows] = await db.query(
      `SELECT prodi_koor, id_kampus, is_koor, name
       FROM lecturer 
       WHERE nim_nik_unit = ? AND is_koor = 1 
       LIMIT 1`,
      [nim_nik_unit]
    );

    // VALIDASI: Cek apakah user adalah coordinator
    if (!lecRows.length) {
      return res.status(403).json({
        success: false,
        message: "You are not authorized as internship coordinator",
      });
    }

    const { prodi_koor, id_kampus, name } = lecRows[0];

    // STEP 2: Query data magang dengan FALLBACK ke internship_letter
    const query = `
  SELECT 
    i.nim,
    si.name AS student_name,
    CONCAT(ps.jenjang, ' ', ps.study_program) AS program_study,
    ps.major AS department,

   -- FALLBACK Class
    COALESCE(
      NULLIF(MAX(il.class), ''),
      '-'
    ) AS class,

    -- FALLBACK Semester
    COALESCE(
      NULLIF(MAX(il.semester), ''),
      '-'
    ) AS semester,

    ? AS internship_coordinator,

    -- Company Name
    COALESCE(
      NULLIF(MAX(c.name), ''),
      NULLIF(MAX(il.company_name), ''),
      '-'
    ) AS company_name,

    -- Company Contact
    COALESCE(
      NULLIF(MAX(c.phone), ''),
      NULLIF(MAX(il.company_contact), ''),
      '-'
    ) AS company_contact,

    -- Company Address
    COALESCE(
      NULLIF(MAX(c.address), ''),
      NULLIF(MAX(il.company_address), ''),
      '-'
    ) AS company_address,

    i.start_date,
    i.end_date,

    COALESCE(NULLIF(si.email, ''), '-') AS email,
    COALESCE(NULLIF(si.no_whatsapp, ''), '-') AS whatsapp_number
    
  FROM internship i
  INNER JOIN student_internship si ON i.nim = si.nim
  LEFT JOIN company c ON i.id_company = c.id_company
  LEFT JOIN program_study ps 
    ON si.program_study = ps.kode_prodi 
    AND si.id_kampus = ps.id_kampus
  
  -- JOIN ke internship_letter untuk fallback data
  LEFT JOIN internship_letter il ON i.nim = il.nim
    AND il.koor_approval = 'ACCEPTED'
    AND il.cdc_approval = 'ACCEPTED'
    AND YEAR(il.start_date) = ?
  
  WHERE i.status = 'ACTIVE'
    AND YEAR(i.start_date) = ?
    AND si.program_study LIKE CONCAT('%', ?, '%')
    AND si.id_kampus = ?
    
  GROUP BY i.nim, si.name, ps.jenjang, ps.study_program, ps.major, i.start_date, i.end_date, si.email, si.no_whatsapp
  ORDER BY i.start_date DESC, si.name ASC
`;

    const [rows] = await db.query(query, [
      name, // internship_coordinator
      currentYear, // YEAR filter untuk internship_letter
      currentYear, // YEAR filter untuk internship
      prodi_koor, // prodi filter
      id_kampus, // kampus filter
    ]);

    // Handle jika tidak ada data
    if (!rows || rows.length === 0) {
      return res.status(404).json({
        success: false,
        message: `No active internship data found for ${prodi_koor} in year ${currentYear}`,
      });
    }

    // STEP 3: Return data
    res.json({
      success: true,
      year: currentYear,
      coordinator: name,
      prodi: prodi_koor,
      count: rows.length,
      data: rows,
    });
  } catch (err) {
    console.error("[EXPORT] Error:", err);
    res.status(500).json({
      success: false,
      message: "Server error",
      error: err.message,
    });
  }
});

module.exports = router;
