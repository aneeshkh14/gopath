<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="gep-main-inner">
    <div class="gep-rank-predictor-wrapper">
        <div class="gep-predictor-header">
            <h2>Rank Predictor</h2>
            <p>Explore an illustrative rank estimate from a mock score. This calculator is not based on verified historical results.</p>
        </div>

        <div class="gep-content-card">
            <div class="gep-form-grid">
                <div class="gep-form-group">
                    <label for="gep-exam-type">Select Exam Category</label>
                    <select class="gep-input" id="gep-exam-type">
                        <option value="ssc_cgl">SSC CGL Tier 1 (Total: 200)</option>
                        <option value="ibps_po">IBPS PO Prelims (Total: 100)</option>
                        <option value="rrb_ntpc">RRB NTPC CBT 2 (Total: 120)</option>
                    </select>
                </div>
                <div class="gep-form-group">
                    <label for="gep-score-input">Average Mock Score</label>
                    <input type="number" class="gep-input" id="gep-score-input" placeholder="e.g. 145.5" min="0" step="any">
                </div>
                <div class="gep-form-group">
                    <label for="gep-category">Category (Reservation)</label>
                    <select class="gep-input" id="gep-category">
                        <option value="ur">General / UR</option>
                        <option value="obc">OBC-NCL</option>
                        <option value="sc_st">SC / ST</option>
                        <option value="ews">EWS</option>
                    </select>
                </div>
                <div class="gep-form-group">
                    <label for="gep-difficulty">Difficulty Level</label>
                    <select class="gep-input" id="gep-difficulty">
                        <option value="moderate">Moderate (Standard)</option>
                        <option value="easy">Easy (High Cutoff)</option>
                        <option value="hard">Hard (Low Cutoff)</option>
                    </select>
                </div>
            </div>

            <button class="gep-btn gep-btn-primary gep-btn-block" id="gep-calculate-rank-btn" style="height: 60px; font-size: 18px; border-radius: 14px; cursor: pointer; border: none; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">Calculate My Predicted Rank</button>
            
            <!-- Predicted Output Card (Hidden by Default) -->
            <div role="status" id="gep-predictor-result" style="display: none; margin-top: 35px; padding: 30px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 20px; transition: all 0.3s ease; text-align: left;">
                <h3 style="margin: 0 0 20px; font-size: 18px; font-weight: 900; color: #1e3a8a; display: flex; align-items: center; gap: 10px;">📊 Projected Rank Output</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                    <div style="background: #fff; border: 1px solid #dbeafe; padding: 15px; border-radius: 12px; text-align: center;">
                        <span style="font-size: 11px; font-weight: 800; color: #3b82f6; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px;">Estimated Percentile</span>
                        <strong id="gep-predicted-percentile" style="font-size: 24px; font-weight: 900; color: #1e3a8a;">95.2%</strong>
                    </div>
                    <div style="background: #fff; border: 1px solid #dbeafe; padding: 15px; border-radius: 12px; text-align: center;">
                        <span style="font-size: 11px; font-weight: 800; color: #3b82f6; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px;">Predicted AIR Range</span>
                        <strong id="gep-predicted-air" style="font-size: 24px; font-weight: 900; color: #1e3a8a;">4,500 - 6,200</strong>
                    </div>
                </div>
                <div style="background: #fff; border: 1px solid #dbeafe; padding: 15px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <span style="font-size: 11px; font-weight: 800; color: #3b82f6; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 2px;">Illustrative score band</span>
                        <strong id="gep-predicted-chances" style="font-size: 14px; font-weight: 800; color: #10b981;">HIGH</strong>
                    </div>
                    <span id="gep-chances-badge" style="font-size: 24px;">🟢</span>
                </div>
                <p id="gep-predicted-note" style="margin: 0; font-size: 13px; font-weight: 600; color: #1e3a8a; line-height: 1.5; font-style: italic;"></p>
            </div>

            <div id="gep-predictor-static-note" style="margin-top: 40px; padding: 30px; background: #f8fafc; border-radius: 16px; text-align: center;">
                <p style="color: var(--gep-text-muted); font-size: 14px; margin-bottom: 10px;">PROJECTION SUMMARY</p>
                <p style="font-size: 13px; font-style: italic;">"Enter your average mock test score above and click calculate to compute your dynamic percentile and rank estimation using an illustrative model. This is not an official rank or selection prediction."</p>
            </div>
        </div>

        <div class="gep-predictor-features">
            <div style="padding: 20px;">
                <span style="font-size: 32px; display: block; margin-bottom: 10px;">📉</span>
                <h4 style="font-size: 14px; font-weight: 800;">Real-time Analysis</h4>
            </div>
            <div style="padding: 20px;">
                <span style="font-size: 32px; display: block; margin-bottom: 10px;">🏆</span>
                <h4 style="font-size: 14px; font-weight: 800;">Top 1% Insights</h4>
            </div>
            <div style="padding: 20px;">
                <span style="font-size: 32px; display: block; margin-bottom: 10px;">📊</span>
                <h4 style="font-size: 14px; font-weight: 800;">Percentile Scoring</h4>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const btn = document.getElementById("gep-calculate-rank-btn");
    if (!btn) return;
    btn.addEventListener("click", function() {
        const score = parseFloat(document.getElementById("gep-score-input").value);
        const exam = document.getElementById("gep-exam-type").value;
        const category = document.getElementById("gep-category").value;
        const difficulty = document.getElementById("gep-difficulty").value;
        
        if (isNaN(score) || score < 0) {
            alert("Please enter a valid mock score.");
            return;
        }
        
        let maxScore = 200;
        let baseMean = 120;
        let baseSD = 25;
        let totalCandidates = 150000;
        
        if (exam === 'ibps_po') {
            maxScore = 100;
            baseMean = 55;
            baseSD = 12;
            totalCandidates = 100000;
        } else if (exam === 'rrb_ntpc') {
            maxScore = 120;
            baseMean = 75;
            baseSD = 15;
            totalCandidates = 120000;
        }
        
        if (score > maxScore) {
            alert("Mock score cannot exceed the maximum score of " + maxScore + " for this exam.");
            return;
        }
        
        // Shift mean based on difficulty
        if (difficulty === 'easy') {
            baseMean += (baseSD * 0.4);
        } else if (difficulty === 'hard') {
            baseMean -= (baseSD * 0.4);
        }
        
        // Shift mean based on reservation category factor
        let reservationMultiplier = 1.0;
        if (category === 'obc') {
            reservationMultiplier = 0.93;
        } else if (category === 'ews') {
            reservationMultiplier = 0.91;
        } else if (category === 'sc_st') {
            reservationMultiplier = 0.82;
        }
        
        const adjustedMean = baseMean * reservationMultiplier;
        
        // Calculate z-score
        const z = (score - adjustedMean) / baseSD;
        
        // Cumulative standard normal distribution approximation (sigmoidal percentile)
        const percentile = 100 * (1 / (1 + Math.exp(-1.702 * z)));
        
        // Estimated rank range
        const estimatedRank = Math.max(1, Math.round(totalCandidates * (1 - (percentile / 100))));
        const lowerRank = Math.max(1, Math.round(estimatedRank * 0.85));
        const upperRank = Math.round(estimatedRank * 1.15);
        
        // Determine chances
        let chances = "LOW";
        let badge = "🔴";
        let color = "#ef4444";
        if (percentile >= 90) {
            chances = "VERY HIGH";
            badge = "🔥";
            color = "#10b981";
        } else if (percentile >= 75) {
            chances = "HIGH";
            badge = "🟢";
            color = "#10b981";
        } else if (percentile >= 50) {
            chances = "MODERATE";
            badge = "🟡";
            color = "#f59e0b";
        }
        
        document.getElementById("gep-predicted-percentile").textContent = percentile.toFixed(1) + "%";
        document.getElementById("gep-predicted-air").textContent = lowerRank.toLocaleString() + " - " + upperRank.toLocaleString();
        
        const chancesEl = document.getElementById("gep-predicted-chances");
        chancesEl.textContent = chances;
        chancesEl.style.color = color;
        document.getElementById("gep-chances-badge").textContent = badge;
        
        document.getElementById("gep-predicted-note").innerHTML = "A score of <strong>" + score + "</strong> placing you in the <strong>" + percentile.toFixed(1) + "th percentile</strong> means you are ahead of approximately <strong>" + Math.round(totalCandidates * (percentile/100)).toLocaleString() + "</strong> hypothetical candidates in this model. Actual ranks depend on real exam results.";
        
        document.getElementById("gep-predictor-static-note").style.display = "none";
        document.getElementById("gep-predictor-result").style.display = "block";
    });
});
</script>
