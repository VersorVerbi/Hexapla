<div id="translModal" class="modal hidden">
    <div id="translBox" class="modal-content">
        <label for="translSelect">Pick a translation to add:</label><BR />
        <select id="translSelect" name="translSelect">
            <?php
                $previousLanguage = "";
                foreach ($allTranslations as $t) {
                    if (!in_array($t[0], $translations)) {
                        if ($t[3] != $previousLanguage) {
                            $previousLanguage = $t[3];
                            echo "</optgroup>";
                            echo "<optgroup label='$previousLanguage'>";
                        }
                        echo "<option value='" . $t[0] . "'>" . $t[1] . " (" . $t[2] . ")</option>";
                    }
                }
            ?>
        </select><BR />
        <input type="button" value="Cancel" id="cancelAdd" name="cancelAdd" />
        <input type="submit" value="OK" id="addTlSubmit" name="addTlSubmit" />
    </div>
</div>