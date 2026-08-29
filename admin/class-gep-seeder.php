<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class GEP_Seeder {
    public static function seed_test_data() {
        global $wpdb;

        // 1. Create Categories (Trending Skill Tracks)
        $categories = array(
            array('name' => 'English Typing', 'slug' => 'english-typing', 'description' => 'Master key layouts and typing speed from scratch.'),
            array('name' => 'Data Entry', 'slug' => 'data-entry', 'description' => 'Learn industry-standard form entry and database auditing.'),
            array('name' => 'MS Excel Mastery', 'slug' => 'excel-mastery', 'description' => 'Formulas, charts, dashboards, and automated spreadsheets.'),
            array('name' => 'Web Design', 'slug' => 'web-design', 'description' => 'Build responsive layouts using modern HTML, CSS, and styling.'),
            array('name' => 'Public Speaking', 'slug' => 'public-speaking', 'description' => 'Vocal poise, presentation formatting, and verbal confidence.')
        );

        $cat_ids = array();
        foreach ($categories as $cat) {
            $existing_cat = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}gep_categories WHERE slug = %s", $cat['slug']));
            if (!$existing_cat) {
                $wpdb->insert($wpdb->prefix . 'gep_categories', $cat);
                $cat_ids[$cat['slug']] = $wpdb->insert_id;
            } else {
                $cat_ids[$cat['slug']] = $existing_cat;
            }
        }
        $typing_id = $cat_ids['english-typing'];
        $data_id   = $cat_ids['data-entry'];
        $excel_id  = $cat_ids['excel-mastery'];
        $web_id    = $cat_ids['web-design'];
        $speech_id = $cat_ids['public-speaking'];

        // 2. Create Courses (Priced 499 to 999 with real practical curriculums)
        $courses = array(
            'typing' => array(
                'title' => 'Touch Typing Masterclass: 0 to 80 WPM',
                'description' => 'Learn proper finger placement, muscle memory shortcuts, key mapping, ergonomics, and daily speed drills to touch type flawlessly without looking.',
                'instructor' => 'Ugant Sharma',
                'price' => 499,
                'category_id' => $typing_id,
                'status' => 'publish',
                'created_at' => current_time('mysql')
            ),
            'data' => array(
                'title' => 'Professional Data Entry Operator Career Course',
                'description' => 'Master speed typing, numeric keypad mechanics, alphanumeric data processing systems, data auditing, and structural spreadsheet entry protocols.',
                'instructor' => 'Ugant Sharma',
                'price' => 599,
                'category_id' => $data_id,
                'status' => 'publish',
                'created_at' => current_time('mysql')
            ),
            'excel' => array(
                'title' => 'Microsoft Excel from Beginner to Advanced Analyst',
                'description' => 'Learn basic cells, lookup functions (VLOOKUP, XLOOKUP, INDEX/MATCH), pivot charts, interactive visualization dashboards, and macro scripting from scratch.',
                'instructor' => 'Ugant Sharma',
                'price' => 799,
                'category_id' => $excel_id,
                'status' => 'publish',
                'created_at' => current_time('mysql')
            ),
            'web' => array(
                'title' => 'Responsive Web Design & Modern CSS Layouts',
                'description' => 'Design interfaces using flexbox, css grids, fluid typography, media queries, keyframe animations, and custom theme systems from absolute scratch.',
                'instructor' => 'Ugant Sharma',
                'price' => 899,
                'category_id' => $web_id,
                'status' => 'publish',
                'created_at' => current_time('mysql')
            ),
            'speech' => array(
                'title' => 'Public Speaking & Verbal Presentation Mastery',
                'description' => 'Overcome stage fright, master vocal modulation, persuasive communication frameworks, slide deck designs, and executive body language.',
                'instructor' => 'Ugant Sharma',
                'price' => 999,
                'category_id' => $speech_id,
                'status' => 'publish',
                'created_at' => current_time('mysql')
            )
        );

        $course_ids = array();
        foreach ($courses as $key => $course) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}gep_courses WHERE title = %s", $course['title']));
            if (!$exists) {
                $wpdb->insert($wpdb->prefix . 'gep_courses', $course);
                $course_ids[$key] = $wpdb->insert_id;
            } else {
                $course_ids[$key] = $exists;
            }
        }

        // 3. Create Real Lesson Lectures & Study Guide PDFs (Learn from scratch)
        $lessons = array(
            // Touch Typing
            array(
                'course_id' => $course_ids['typing'],
                'title' => '1. Home Row Finger Placement & Muscle Memory',
                'video_url' => 'https://www.youtube.com/watch?v=1qy5X2s9-gA',
                'video_source' => 'youtube',
                'duration' => '12:30',
                'order_no' => 1,
                'description' => 'Master finger positioning on A-S-D-F and J-K-L-; rows. Practice key returns without visual aids.',
                'pdf_url' => 'https://www.gopath.in/downloads/typing-home-row-drills.pdf'
            ),
            array(
                'course_id' => $course_ids['typing'],
                'title' => '2. Top Row & Bottom Row Extensions',
                'video_url' => 'https://www.youtube.com/watch?v=0tS67tNnJjI',
                'video_source' => 'youtube',
                'duration' => '15:45',
                'order_no' => 2,
                'description' => 'Learn how to stretch your fingers to the Q-W-E-R-T and Z-X-C-V rows while anchoring on the home row.',
                'pdf_url' => 'https://www.gopath.in/downloads/typing-full-keyboard-drills.pdf'
            ),

            // Data Entry
            array(
                'course_id' => $course_ids['data'],
                'title' => '1. Data Entry Interface Formats & Data Validation Rules',
                'video_url' => 'https://www.youtube.com/watch?v=5y2KOp20-Yk',
                'video_source' => 'youtube',
                'duration' => '10:15',
                'order_no' => 1,
                'description' => 'Introduction to standard form structures, auditing databases, and preventing manual entry errors.',
                'pdf_url' => 'https://www.gopath.in/downloads/data-entry-standards-guide.pdf'
            ),

            // Excel Analyst
            array(
                'course_id' => $course_ids['excel'],
                'title' => '1. Excel Grid Interface & Arithmetic Formulas',
                'video_url' => 'https://www.youtube.com/watch?v=Vl0hZHyVGyA',
                'video_source' => 'youtube',
                'duration' => '20:00',
                'order_no' => 1,
                'description' => 'Learn row/column coordinates, cell references, autosum, basic averages, and data formatting.',
                'pdf_url' => 'https://www.gopath.in/downloads/excel-basics-reference-sheet.pdf'
            ),
            array(
                'course_id' => $course_ids['excel'],
                'title' => '2. Advanced Lookup Functions (XLOOKUP & VLOOKUP)',
                'video_url' => 'https://www.youtube.com/watch?v=Jy5y2KOp20-Yk',
                'video_source' => 'youtube',
                'duration' => '18:30',
                'order_no' => 2,
                'description' => 'Master vertical data lookups, exact vs approximate matches, and the flexible XLOOKUP arrays.',
                'pdf_url' => 'https://www.gopath.in/downloads/excel-lookup-cheatsheet.pdf'
            ),

            // Web Design
            array(
                'course_id' => $course_ids['web'],
                'title' => '1. Semantic HTML5 & Modern Flexbox Layouts',
                'video_url' => 'https://www.youtube.com/watch?v=UB1O30FDF-A',
                'video_source' => 'youtube',
                'duration' => '25:00',
                'order_no' => 1,
                'description' => 'Create structural web containers and align items dynamically with CSS Flexbox rules.',
                'pdf_url' => 'https://www.gopath.in/downloads/web-design-html-basics.pdf'
            ),
            array(
                'course_id' => $course_ids['web'],
                'title' => '2. CSS Grid & Responsive Media Queries',
                'video_url' => 'https://www.youtube.com/watch?v=3YW-A5T-g8',
                'video_source' => 'youtube',
                'duration' => '22:15',
                'order_no' => 2,
                'description' => 'Build fluid grid layouts that automatically scale down for mobile devices and tablets.',
                'pdf_url' => 'https://www.gopath.in/downloads/css-flex-grid-cheatsheet.pdf'
            ),

            // Public Speaking
            array(
                'course_id' => $course_ids['speech'],
                'title' => '1. Stage Fright Management & Vocal Poise',
                'video_url' => 'https://www.youtube.com/watch?v=i5x2s9-gA',
                'video_source' => 'youtube',
                'duration' => '15:00',
                'order_no' => 1,
                'description' => 'Learn controlled breathing techniques, stance, and modulation to project confidence on stage.',
                'pdf_url' => 'https://www.gopath.in/downloads/public-speaking-confidence-tips.pdf'
            )
        );

        foreach ($lessons as $lesson) {
            if (!$lesson['course_id']) continue;
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}gep_lessons WHERE title = %s AND course_id = %d", $lesson['title'], $lesson['course_id']));
            if (!$exists) {
                $wpdb->insert($wpdb->prefix . 'gep_lessons', $lesson);
            }
        }

        // 4. Create Evaluation Practice Questions (MCQs)
        $questions = array(
            array(
                'title' => 'Which finger is responsible for pressing the Spacebar in Touch Typing?',
                'option_a' => 'Index Finger', 'option_b' => 'Thumb', 'option_c' => 'Pinky Finger', 'option_d' => 'Middle Finger',
                'correct_answer' => 'B', 'explanation' => 'Either left or right thumb should be used to press the Spacebar.',
                'category_id' => $typing_id
            ),
            array(
                'title' => 'What is the keyboard shortcut to copy only values (Paste Special Values) in Excel?',
                'option_a' => 'Ctrl + V', 'option_b' => 'Ctrl + Alt + V, then V', 'option_c' => 'Ctrl + Shift + C', 'option_d' => 'Ctrl + Shift + V',
                'correct_answer' => 'B', 'explanation' => 'Ctrl+Alt+V opens the Paste Special dialog, and V selects values.',
                'category_id' => $excel_id
            ),
            array(
                'title' => 'Which CSS property defines a Flexbox container layout?',
                'option_a' => 'display: block;', 'option_b' => 'layout: flex;', 'option_c' => 'display: flex;', 'option_d' => 'align: flex;',
                'correct_answer' => 'C', 'explanation' => 'display: flex; initializes the flex formatting context.',
                'category_id' => $web_id
            )
        );

        $q_ids = array();
        foreach ($questions as $q) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}gep_questions WHERE title = %s", $q['title']));
            if (!$exists) {
                $wpdb->insert($wpdb->prefix . 'gep_questions', $q);
                $q_ids[] = $wpdb->insert_id;
            } else {
                $q_ids[] = $exists;
            }
        }

        // Seed Comprehension Passage Question
        $passage_title_en = 'Touch Typing is the ability to use muscle memory to find keys fast without looking at the keyboard. It significantly increases typing speed and accuracy, reducing cognitive load on the writer.';
        $passage_title_hi = 'स्पर्श टाइपिंग कीबोर्ड को देखे बिना कुंजियों को तेजी से खोजने के लिए मांसपेशी स्मृति (मसल मेमोरी) का उपयोग करने की क्षमता है। यह लिखने वाले पर संज्ञानात्मक भार को कम करते हुए टाइपिंग की गति और सटीकता को महत्वपूर्ण रूप से बढ़ाता है।';
        $passage_trans = wp_json_encode(array('title' => $passage_title_hi), JSON_UNESCAPED_UNICODE);
        
        $passage_q_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}gep_questions WHERE question_type = 'passage' AND title = %s", $passage_title_en));
        if (!$passage_q_id) {
            $wpdb->insert($wpdb->prefix . 'gep_questions', array(
                'title' => $passage_title_en,
                'question_type' => 'passage',
                'category_id' => $typing_id,
                'translation_enabled' => 1,
                'translated_data' => $passage_trans
            ));
            $passage_q_id = $wpdb->insert_id;
        }

        // Seed MCQs linked to this Passage
        $passage_mcqs = array(
            array(
                'title' => 'What memory does touch typing rely on to find keys?',
                'option_a' => 'Visual memory', 'option_b' => 'Muscle memory', 'option_c' => 'Short-term memory', 'option_d' => 'Auditory memory',
                'correct_answer' => 'B', 
                'explanation' => 'Touch typing relies entirely on finger muscle memory developed through practice.',
                'category_id' => $typing_id,
                'passage_id' => $passage_q_id,
                'translation_enabled' => 1,
                'translated_data' => wp_json_encode(array(
                    'title' => 'कुंजियों को खोजने के लिए स्पर्श टाइपिंग किस स्मृति पर निर्भर करती है?',
                    'option_a' => 'दृश्य स्मृति (विजुअल मेमोरी)', 'option_b' => 'मांसपेशी स्मृति (मसल मेमोरी)', 'option_c' => 'अल्पकालिक स्मृति', 'option_d' => 'श्रवण स्मृति',
                    'explanation' => 'स्पर्श टाइपिंग अभ्यास के माध्यम से विकसित उंगली की मांसपेशी स्मृति पर निर्भर करती है।'
                ), JSON_UNESCAPED_UNICODE)
            ),
            array(
                'title' => 'What is one benefit of touch typing mentioned in the passage?',
                'option_a' => 'It increases finger length', 'option_b' => 'It reduces cognitive load', 'option_c' => 'It improves eyesight', 'option_d' => 'It helps you sleep',
                'correct_answer' => 'B',
                'explanation' => 'The passage states that touch typing reduces cognitive load on the writer.',
                'category_id' => $typing_id,
                'passage_id' => $passage_q_id,
                'translation_enabled' => 1,
                'translated_data' => wp_json_encode(array(
                    'title' => 'गद्यांश में स्पर्श टाइपिंग का क्या लाभ बताया गया है?',
                    'option_a' => 'यह उंगली की लंबाई बढ़ाता है', 'option_b' => 'यह संज्ञानात्मक भार को कम करता है', 'option_c' => 'यह दृष्टि में सुधार करता है', 'option_d' => 'यह आपको सोने में मदद करता है',
                    'explanation' => 'गद्यांश में कहा गया है कि स्पर्श टाइपिंग लिखने वाले पर संज्ञानात्मक भार को कम करती है।'
                ), JSON_UNESCAPED_UNICODE)
            )
        );

        $passage_mcq_ids = array();
        foreach ($passage_mcqs as $pm) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}gep_questions WHERE title = %s", $pm['title']));
            if (!$exists) {
                $wpdb->insert($wpdb->prefix . 'gep_questions', $pm);
                $passage_mcq_ids[] = $wpdb->insert_id;
            } else {
                $passage_mcq_ids[] = $exists;
            }
        }

        // 5. Create Practice Assessment Tests
        $tests = array(
            array(
                'title' => 'MS Excel formulas Practice Quiz',
                'slug' => 'excel-quiz-1',
                'category_id' => $excel_id,
                'price' => 0, 'is_free' => 1,
                'duration_minutes' => 15, 'total_marks' => 10, 'pass_marks' => 4,
                'status' => 'publish'
            ),
            array(
                'title' => 'Touch Typing Basic Knowledge Test',
                'slug' => 'typing-quiz-1',
                'category_id' => $typing_id,
                'price' => 0, 'is_free' => 1,
                'duration_minutes' => 10, 'total_marks' => 10, 'pass_marks' => 4,
                'status' => 'publish'
            )
        );

        foreach ($tests as $index => $test) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}gep_tests WHERE slug = %s", $test['slug']));
            if (!$exists) {
                $wpdb->insert($wpdb->prefix . 'gep_tests', $test);
                $t_id = $wpdb->insert_id;

                // Link relevant questions
                if (isset($q_ids[$index])) {
                    $wpdb->insert($wpdb->prefix . 'gep_test_questions', array(
                        'test_id' => $t_id,
                        'question_id' => $q_ids[$index],
                        'order_no' => 1
                    ));
                }
            }
        }

        // Always ensure passage MCQs are linked to typing-quiz-1
        $t_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}gep_tests WHERE slug = %s", 'typing-quiz-1'));
        if ($t_id && !empty($passage_mcq_ids)) {
            // First clean up existing connections for these passage questions to avoid duplicates
            foreach ($passage_mcq_ids as $p_mcq_id) {
                $wpdb->delete($wpdb->prefix . 'gep_test_questions', array(
                    'test_id' => $t_id,
                    'question_id' => $p_mcq_id
                ));
            }
            
            // Link the passage MCQs
            $order = 2;
            foreach ($passage_mcq_ids as $p_mcq_id) {
                $wpdb->insert($wpdb->prefix . 'gep_test_questions', array(
                    'test_id' => $t_id,
                    'question_id' => $p_mcq_id,
                    'order_no' => $order++
                ));
            }
        }

        // 6. Create Live Doubt Solving & Review Classes
        $live_classes = array(
            array(
                'title' => 'MS Excel Advanced Formulas & VLOOKUP Q&A',
                'instructor' => 'John Walkenbach',
                'course_id' => $course_ids['excel'],
                'status' => 'scheduled',
                'scheduled_at' => date('Y-m-d H:i:s', strtotime('+2 days 15:00:00')),
                'meeting_url' => 'https://zoom.us/j/example-excel-doubt'
            ),
            array(
                'title' => 'CSS Grid Layouts Live Code Review Session',
                'instructor' => 'Brad Traversy',
                'course_id' => $course_ids['web'],
                'status' => 'recorded',
                'scheduled_at' => date('Y-m-d H:i:s', strtotime('-1 days 11:00:00')),
                'recording_url' => 'https://vimeo.com/example-web-review'
            )
        );

        foreach ($live_classes as $live) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}gep_live_classes WHERE title = %s", $live['title']));
            if (!$exists) {
                $wpdb->insert($wpdb->prefix . 'gep_live_classes', $live);
            }
        }

        // 7. Seed Premium Standalone Video Lectures
        $table_lectures = $wpdb->prefix . 'gep_lectures';
        $lectures = array(
            array(
                'title' => 'Sanskrit Vyakaran: Introduction to Panini Ashtadhyayi',
                'video_url' => 'https://vimeo.com/769798717',
                'video_source' => 'vimeo',
                'thumbnail' => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?auto=format&fit=crop&q=80&w=600',
                'instructor' => 'Dr. Keshav Dev',
                'category_id' => $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}gep_categories WHERE slug = %s", 'upsc')) ?: 0,
                'subcategory_id' => 0,
                'duration' => '45 mins',
                'description' => 'A comprehensive high-fidelity lecture on Paninis grammar constructs, rules, and Ashtadhyayi foundations.',
                'status' => 'publish',
                'created_at' => current_time('mysql')
            ),
            array(
                'title' => 'UPSC History: Vedic Culture and Philosophy',
                'video_url' => 'https://vimeo.com/769798717',
                'video_source' => 'vimeo',
                'thumbnail' => 'https://images.unsplash.com/photo-1457369804613-52c61a468e7d?auto=format&fit=crop&q=80&w=600',
                'instructor' => 'Prof. S. R. Goyal',
                'category_id' => $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}gep_categories WHERE slug = %s", 'upsc')) ?: 0,
                'subcategory_id' => 0,
                'duration' => '1 hr 15 mins',
                'description' => 'Detailed video lecture reviewing the social structure, literature, and philosophical transitions during the Vedic period.',
                'status' => 'publish',
                'created_at' => current_time('mysql')
            )
        );

        foreach ($lectures as $lec) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_lectures WHERE title = %s", $lec['title']));
            if (!$exists) {
                $wpdb->insert($table_lectures, $lec);
            }
        }
    }
}
