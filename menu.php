<div id="menuwrap">
    <div id="menu-btn-wrap">
        <a href="#" id="menu-button"></a>
    </div>
    <div id="menu">
        <ul>
            <li><a href="javascript:void(0)">Advanced Search</a></li>
            <hr />
            <h4>Options</h4>
            <?php if (isset($db)) { ?>
            <li>
                <label for="docgroup">Document Group:</label><br />
                <select id="docgroup" name="docgroup">
                    <?php
                        $q = $db->query("CALL DocGroupList();");
                        $docgroups = $q->fetch_all(MYSQLI_ASSOC);
                        
                        for ($i = 0; $i < count($docgroups); $i++) {
                            echo "<option value='" . $docgroups[$i]['id'] . "'>" . $docgroups[$i]['name'] . "</option>";
                        }
                    ?>
                </select>
            </li>
            <li><input type="checkbox" name="scroll" id="scroll" /><label for="scroll">Scroll together</label></li>
            <?php } ?>
            <li><input type="checkbox" name="menuup" id="menuup" /><label for="menuup">Keep this menu open</label></li>
            <!-- add options for diff by word & diff case-sensitive -->
            <!-- add option to keep diff turned on? -->
            <!-- add option for which document group to loop through -->
            <hr />
            <h4>Navigation</h4>
            <li><a href="about.php">About Us</a></li>
            <li><a href="help.php">How You Can Help</a></li>
            <li><a href="copyrights.php">Copyright Information</a></li>
            <li><a href="privacy.php">Privacy Policy</a></li>
            <li><a href="tos.php">Terms of Service</a></li>
        </ul>
        <div id="menubottom">
            <h4><a href="index.php">ModernHexapla.com</a></h4>
        </div>
    </div>
</div>